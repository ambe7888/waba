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
     */
    public function handle(WhatsappWebhookReceived $event): void
    {
        $data = $event->webhookData;
        $vendorUid = $event->vendorUid;

        $entry = Arr::get($data, 'entry', []);
        $changes = Arr::get($entry, '0.changes', []);

        foreach ($changes as $change) {
            $field = Arr::get($change, 'field');
            if ($field !== 'calls') {
                continue;
            }

            $value = Arr::get($change, 'value', []);
            $callId = Arr::get($value, 'call_id');
            $action = Arr::get($value, 'action');
            $sessionSdp = Arr::get($value, 'session.sdp');
            $sessionSdpType = Arr::get($value, 'session.sdp_type');

            Log::info('WhatsApp Call Webhook received', [
                'vendor_uid' => $vendorUid,
                'call_id' => $callId,
                'action' => $action,
                'sdp_type' => $sessionSdpType,
                'has_sdp' => !empty($sessionSdp),
                'raw_value' => $value,
            ]);

            // Broadcast call events to the vendor frontend via Echo
            event(new VendorChannelBroadcast($vendorUid, [
                'callEvent' => [
                    'call_id' => $callId,
                    'action' => $action,
                    'sdp' => $sessionSdp,
                    'sdp_type' => $sessionSdpType,
                ]
            ]));
        }
    }
}
