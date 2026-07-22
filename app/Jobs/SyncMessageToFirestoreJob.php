<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\FirestoreService;
use App\Yantrana\Components\WhatsAppService\Models\WhatsAppMessageLogModel;
use App\Yantrana\Components\Contact\Models\ContactModel;

class SyncMessageToFirestoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $messageLogId;

    public function __construct($messageLogId)
    {
        $this->messageLogId = $messageLogId;
    }

    public function handle()
    {
        $messageLog = WhatsAppMessageLogModel::find($this->messageLogId);
        if (!$messageLog) {
            return;
        }

        // We need the contact's UID to organize chats by contact
        $contact = ContactModel::find($messageLog->contacts__id);
        if (!$contact) {
            return;
        }

        $firestore = new FirestoreService();

        // Convert the model to the exact format expected by ChatMessage in Flutter
        $data = [
            'uid' => $messageLog->_uid,
            'message' => $messageLog->message ?: '',
            'is_incoming' => (bool)$messageLog->is_incoming_message,
            'timestamp' => $messageLog->messaged_at ? $messageLog->messaged_at->toIso8601String() : now()->toIso8601String(),
            'type' => 'text', // default
            'status' => $messageLog->status,
            'is_system_message' => (bool)$messageLog->is_system_message,
        ];

        // Basic check for media (if __data contains media values)
        if (!empty($messageLog->__data['media_values'])) {
            $mediaValues = $messageLog->__data['media_values'];
            $data['type'] = $mediaValues['type'] ?? 'media';
            $data['media_url'] = $mediaValues['link'] ?? '';
            if (empty($data['message']) && !empty($mediaValues['caption'])) {
                $data['message'] = $mediaValues['caption'];
            }
        }

        $collectionPath = "chats/{$contact->_uid}/messages";
        
        $firestore->setDocument($collectionPath, $messageLog->_uid, $data);
    }
}
