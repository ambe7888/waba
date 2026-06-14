<?php

namespace App\Listeners;

use App\Events\WhatsappWebhookReceived;
use App\Events\VendorChannelBroadcast;
use Illuminate\Support\Arr;
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

            // Process "calls" array entries (connect, terminate events with SDP)
            $calls = Arr::get($value, 'calls', []);
            foreach ($calls as $call) {
                $callId = Arr::get($call, 'id');
                $callEvent = Arr::get($call, 'event'); // "connect" or "terminate"
                $sessionSdp = Arr::get($call, 'session.sdp');
                $sessionSdpType = Arr::get($call, 'session.sdp_type');

                Log::info('HandleCallWebhook: call event parsed', [
                    'vendor_uid' => $vendorUid,
                    'call_id' => $callId,
                    'event' => $callEvent,
                    'sdp_type' => $sessionSdpType,
                    'has_sdp' => !empty($sessionSdp),
                ]);

                // Broadcast call event to the vendor frontend via Echo
                event(new VendorChannelBroadcast($vendorUid, [
                    'callEvent' => [
                        'call_id' => $callId,
                        'event' => $callEvent,
                        'sdp' => $sessionSdp,
                        'sdp_type' => $sessionSdpType,
                    ]
                ]));
            }

            // Process "statuses" array entries (RINGING, ACCEPTED status updates)
            $statuses = Arr::get($value, 'statuses', []);
            foreach ($statuses as $status) {
                $callId = Arr::get($status, 'id');
                $callStatus = Arr::get($status, 'status'); // "RINGING", "ACCEPTED"

                Log::info('HandleCallWebhook: status event parsed', [
                    'vendor_uid' => $vendorUid,
                    'call_id' => $callId,
                    'status' => $callStatus,
                ]);

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
}
