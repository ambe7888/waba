<?php

namespace App\Listeners;

use App\Events\WhatsappWebhookReceived;
use App\Events\VendorChannelBroadcast;
use App\Yantrana\Components\Vendor\Models\VendorModel;
use App\Yantrana\Components\Contact\Models\ContactModel;
use App\Yantrana\Components\WhatsAppService\Models\CallModel;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HandleCallWebhook
{
    /**
     * Handle the WhatsappWebhookReceived event for "calls" field.
     *
     * Meta webhook structure for calls:
     * - Connect event (with SDP): value.calls[0].{id, event:"connect", session:{sdp, sdp_type}}
     * - Status events: value.statuses[0].{id, status:"RINGING"|"ACCEPTED", type:"call"}
     * - Terminate event: value.calls[0].{id, event:"terminate", duration, status:"COMPLETED"}
     */
    public function handle(WhatsappWebhookReceived $event): void
    {
        $data = $event->webhookData;
        $vendorUid = $event->vendorUid;

        Log::info('HandleCallWebhook: raw webhook data received', [
            'vendor_uid' => $vendorUid,
            'raw_data' => json_encode($data),
        ]);

        $entry = Arr::get($data, 'entry', []);
        $changes = Arr::get($entry, '0.changes', []);

        foreach ($changes as $change) {
            $field = Arr::get($change, 'field');
            if ($field !== 'calls') {
                continue;
            }

            $value = Arr::get($change, 'value', []);

            // Meta can send 'calls' as an array OR directly inside 'value'
            $calls = Arr::get($value, 'calls', []);
            if (empty($calls) && isset($value['id']) && isset($value['event'])) {
                $calls = [$value]; // wrap direct object into array
            }

            foreach ($calls as $call) {
                $callId = Arr::get($call, 'id');
                $callEvent = Arr::get($call, 'event'); // "connect" or "terminate"
                $callFrom = Arr::get($call, 'from');
                $callTo = Arr::get($call, 'to');
                $callDirection = Arr::get($call, 'direction'); // "USER_INITIATED" or "BUSINESS_INITIATED"
                $callTerminateStatus = Arr::get($call, 'status'); // terminate only: COMPLETED, FAILED...
                $callDuration = Arr::get($call, 'duration'); // terminate only, seconds
                $sessionSdp = Arr::get($call, 'session.sdp');
                if (empty($sessionSdp)) {
                    $sessionSdp = Arr::get($call, 'connection.webrtc.sdp');
                }
                
                $sessionSdpType = Arr::get($call, 'session.sdp_type');
                if (empty($sessionSdpType)) {
                    // if not provided, assume answer since Meta responds to our offer
                    $sessionSdpType = 'answer';
                }

                // Sanitize SDP to ensure it has proper \r\n line endings (WebRTC requirement)
                if (!empty($sessionSdp)) {
                    $lines = preg_split('/\r\n|\r|\n/', $sessionSdp);
                    $clean = [];
                    foreach ($lines as $line) {
                        $clean[] = $line;
                    }
                    $sessionSdp = implode("\r\n", $clean) . "\r\n";
                }

                Log::info('HandleCallWebhook: call event parsed', [
                    'vendor_uid' => $vendorUid,
                    'call_id' => $callId,
                    'event' => $callEvent,
                    'sdp_type' => $sessionSdpType,
                    'has_sdp' => !empty($sessionSdp),
                ]);

                // Meta redelivers the same webhook (observed ~1s apart in
                // production). Without dedup, a repeated "connect" makes the
                // browser try to apply the same SDP answer twice -- which
                // throws (a peer connection can't re-negotiate the same
                // answer once stable) and was tearing down calls that had
                // just connected. Skip exact repeats within a short window.
                $dedupKey = 'call_event_dedup:' . $vendorUid . ':' . $callId . ':' . $callEvent . ':' . $sessionSdpType;
                if (Cache::has($dedupKey)) {
                    Log::info('HandleCallWebhook: duplicate call event skipped', ['call_id' => $callId, 'event' => $callEvent]);
                    continue;
                }
                Cache::put($dedupKey, true, 30);

                // Record a permanent call-history entry once a call actually
                // ends -- this is the only event carrying the final status
                // and duration, so it's the natural point to write one row
                // per call (connect/status events are transient, DB-free).
                if ($callEvent === 'terminate') {
                    $this->recordCallHistory($vendorUid, $callId, $callFrom, $callTo, $callDirection, $callTerminateStatus, $callDuration);
                }

                // Broadcast call event to the vendor frontend via Echo
                event(new VendorChannelBroadcast($vendorUid, [
                    'callEvent' => [
                        'call_id' => $callId,
                        'event' => $callEvent,
                        'sdp' => $sessionSdp,
                        'sdp_type' => $sessionSdpType,
                        'from' => $callFrom,
                        'direction' => $callDirection,
                    ]
                ]));
            }

            // Process "statuses" array entries (RINGING, ACCEPTED status updates)
            $statuses = Arr::get($value, 'statuses', []);
            if (empty($statuses) && isset($value['id']) && isset($value['status'])) {
                $statuses = [$value]; // wrap direct object
            }

            foreach ($statuses as $status) {
                $callId = Arr::get($status, 'id');
                $callStatus = Arr::get($status, 'status'); // "RINGING", "ACCEPTED"

                Log::info('HandleCallWebhook: status event parsed', [
                    'vendor_uid' => $vendorUid,
                    'call_id' => $callId,
                    'status' => $callStatus,
                ]);

                $statusDedupKey = 'call_event_dedup:' . $vendorUid . ':' . $callId . ':status:' . $callStatus;
                if (Cache::has($statusDedupKey)) {
                    continue;
                }
                Cache::put($statusDedupKey, true, 30);

                // Broadcast status update to the vendor frontend via Echo
                event(new VendorChannelBroadcast($vendorUid, [
                    'callEvent' => [
                        'call_id' => $callId,
                        'event' => 'status',
                        'status' => $callStatus,
                    ]
                ]));
            }
        }
    }

    /**
     * Write one row to the `calls` table for a WhatsApp Calling conversation
     * that just ended, so it shows up in the contact's call history.
     */
    protected function recordCallHistory($vendorUid, $callId, $callFrom, $callTo, $callDirection, $status, $duration): void
    {
        $vendorId = VendorModel::where('_uid', $vendorUid)->value('_id');
        if (!$vendorId) {
            return;
        }

        // The customer's number is whichever side of from/to isn't our own
        // business-connected number -- for a business-initiated call that's
        // "to", for a user-initiated call that's "from".
        $customerPhone = $callDirection === 'BUSINESS_INITIATED' ? $callTo : $callFrom;
        $customerPhone = preg_replace('/[^0-9]/', '', (string) $customerPhone);

        $contactId = $customerPhone
            ? ContactModel::where(['wa_id' => $customerPhone, 'vendors__id' => $vendorId])->value('_id')
            : null;

        $normalizedStatus = strtolower((string) $status) ?: 'unknown';
        $direction = $callDirection === 'BUSINESS_INITIATED' ? 'outbound' : 'inbound';

        CallModel::updateOrCreate(
            ['call_id' => $callId],
            [
                '_uid' => (string) Str::uuid(),
                'vendors__id' => $vendorId,
                'contacts__id' => $contactId,
                'type' => 'whatsapp',
                'direction' => $direction,
                'status' => $normalizedStatus,
                'duration' => is_numeric($duration) ? (int) $duration : null,
            ]
        );
    }
}
