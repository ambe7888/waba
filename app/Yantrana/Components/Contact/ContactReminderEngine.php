<?php
/**
* ContactReminderEngine.php - Engine file
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Contact;

use App\Yantrana\Base\BaseEngine;
use App\Yantrana\Components\Contact\Repositories\ContactReminderRepository;
use App\Yantrana\Components\Contact\Repositories\ContactRepository;
use App\Yantrana\Components\WhatsAppService\WhatsAppServiceEngine;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ContactReminderEngine extends BaseEngine
{
    /**
     * @var ContactReminderRepository $contactReminderRepository
     */
    protected $contactReminderRepository;

    /**
     * @var ContactRepository $contactRepository
     */
    protected $contactRepository;

    /**
     * @var WhatsAppServiceEngine $whatsAppServiceEngine
     */
    protected $whatsAppServiceEngine;

    /**
     * Constructor
     */
    public function __construct(
        ContactReminderRepository $contactReminderRepository,
        ContactRepository $contactRepository,
        WhatsAppServiceEngine $whatsAppServiceEngine
    ) {
        $this->contactReminderRepository = $contactReminderRepository;
        $this->contactRepository = $contactRepository;
        $this->whatsAppServiceEngine = $whatsAppServiceEngine;
    }

    /**
     * Process Create or Update Contact Reminder
     *
     * @param array $inputData
     * @param string $contactUid
     * @return array
     */
    public function processCreateReminder($inputData, $contactUid)
    {
        $vendorId = getVendorId();
        $contact = $this->contactRepository->fetchIt([
            '_uid' => $contactUid,
            'vendors__id' => $vendorId,
        ]);

        if (__isEmpty($contact)) {
            return $this->engineFailedResponse([], __tr('Contact not found.'));
        }

        // Cancel existing pending reminders for this contact
        $this->contactReminderRepository->updateIt([
            'contacts__id' => $contact->_id,
            'vendors__id' => $vendorId,
            'status' => 1,
        ], [
            'status' => 3, // 3 = cancelled
        ]);

        // Calculate scheduled_at
        $scheduledAt = null;
        if (!empty($inputData['preset_time'])) {
            switch ($inputData['preset_time']) {
                case 'in_2_hours':
                    $scheduledAt = now()->addHours(2);
                    break;
                case 'today_14h':
                    $scheduledAt = now()->setTime(14, 0, 0);
                    if ($scheduledAt->isPast()) {
                        $scheduledAt->addDay();
                    }
                    break;
                case 'today_18h':
                    $scheduledAt = now()->setTime(18, 0, 0);
                    if ($scheduledAt->isPast()) {
                        $scheduledAt->addDay();
                    }
                    break;
                case 'tomorrow_same_time':
                    $scheduledAt = now()->addDay();
                    break;
                case 'custom':
                    if (!empty($inputData['custom_datetime'])) {
                        $scheduledAt = Carbon::parse($inputData['custom_datetime']);
                    }
                    break;
            }
        }

        if (!$scheduledAt) {
            return $this->engineFailedResponse([], __tr('Please select a valid date and time.'));
        }

        $templateFields = [];
        if (!empty($inputData['template_fields']) && is_array($inputData['template_fields'])) {
            $templateFields = $inputData['template_fields'];
        } else {
            foreach ($inputData as $k => $v) {
                if (Str::startsWith($k, 'field_') || Str::startsWith($k, 'header_field_')) {
                    $templateFields[$k] = $v;
                }
            }
        }

        $reminderData = [
            '_uid' => Str::uuid()->toString(),
            'vendors__id' => $vendorId,
            'contacts__id' => $contact->_id,
            'users__id' => getUserId(),
            'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
            'action_type' => $inputData['action_type'] ?? 'notification',
            'title_note' => $inputData['title_note'] ?? '',
            'template_name' => $inputData['template_name'] ?? null,
            'template_language' => $inputData['template_language'] ?? 'fr',
            'status' => 1, // 1 = pending
            '__data' => [
                'template_fields' => $templateFields,
            ],
        ];

        $newReminder = $this->contactReminderRepository->storeIt($reminderData);

        if ($newReminder) {
            $typeText = 'Notification Interne';
            if ($newReminder->action_type === 'auto_message') {
                $typeText = 'WhatsApp Auto (Direct)';
            } elseif ($newReminder->action_type === 'template_message') {
                $typeText = 'WhatsApp Auto (Modèle: ' . $newReminder->template_name . ')';
            }

            $systemMsg = "🔔 Relance programmée pour le " . $scheduledAt->translatedFormat('d/m/Y à H:i') . " ({$typeText}) : " . $newReminder->title_note;
            storeWhatsAppLogChatHistory([
                'status' => 'initialize',
                'contacts__id' => $contact->_id,
                'vendors__id' => $vendorId,
                'contact_wa_id' => $contact->wa_id,
                'is_system_message' => 1,
                'is_incoming_message' => 0,
                'messaged_at' => now(),
                'message' => $systemMsg,
                '__data' => [
                    'system_message_data' => [
                        'message' => $systemMsg
                    ]
                ]
            ]);

            return $this->engineSuccessResponse([
                'reminder' => [
                    '_uid' => $newReminder->_uid,
                    'scheduled_at' => $newReminder->scheduled_at,
                    'scheduled_at_formatted' => $scheduledAt->translatedFormat('d/m/Y à H:i'),
                    'action_type' => $newReminder->action_type,
                    'title_note' => $newReminder->title_note,
                    'template_name' => $newReminder->template_name,
                ]
            ], __tr('Reminder saved successfully!'));
        }

        return $this->engineFailedResponse([], __tr('Failed to save reminder.'));
    }

    /**
     * Process Cancel Contact Reminder
     *
     * @param string $contactUid
     * @return array
     */
    public function processCancelReminder($contactUid)
    {
        $vendorId = getVendorId();
        $contact = $this->contactRepository->fetchIt([
            '_uid' => $contactUid,
            'vendors__id' => $vendorId,
        ]);

        if (__isEmpty($contact)) {
            return $this->engineFailedResponse([], __tr('Contact not found.'));
        }

        $updated = $this->contactReminderRepository->updateIt([
            'contacts__id' => $contact->_id,
            'vendors__id' => $vendorId,
            'status' => 1,
        ], [
            'status' => 3, // 3 = cancelled
        ]);

        if ($updated) {
            $systemMsg = "🔕 Relance annulée par l'agent";
            storeWhatsAppLogChatHistory([
                'status' => 'initialize',
                'contacts__id' => $contact->_id,
                'vendors__id' => $vendorId,
                'contact_wa_id' => $contact->wa_id,
                'is_system_message' => 1,
                'is_incoming_message' => 0,
                'messaged_at' => now(),
                'message' => $systemMsg,
                '__data' => [
                    'system_message_data' => [
                        'message' => $systemMsg
                    ]
                ]
            ]);

            return $this->engineSuccessResponse([], __tr('Reminder cancelled successfully.'));
        }

        return $this->engineFailedResponse([], __tr('No active reminder found for this contact.'));
    }

    /**
     * Execute due reminders (called by Cron Command)
     *
     * @return int Count of executed reminders
     */
    public function executeDueReminders()
    {
        $dueReminders = $this->contactReminderRepository->fetchDueReminders();
        $executedCount = 0;

        foreach ($dueReminders as $reminder) {
            $contact = $this->contactRepository->fetchIt([
                '_id' => $reminder->contacts__id,
            ]);

            if (!$contact) {
                $reminder->status = 3; // Cancel if contact missing
                $reminder->save();
                continue;
            }

            if ($reminder->action_type === 'notification') {
                // Internal notification alert in chat timeline
                $notifMsg = "⏰ [RAPPEL DE RELANCE DÛ] Note : " . ($reminder->title_note ?: 'Relance programmée à cette heure.');
                storeWhatsAppLogChatHistory([
                    'status' => 'initialize',
                    'contacts__id' => $contact->_id,
                    'vendors__id' => $reminder->vendors__id,
                    'contact_wa_id' => $contact->wa_id,
                    'is_system_message' => 1,
                    'is_incoming_message' => 0,
                    'messaged_at' => now(),
                    'message' => $notifMsg,
                    '__data' => [
                        'system_message_data' => [
                            'message' => $notifMsg
                        ]
                    ]
                ]);
            } elseif ($reminder->action_type === 'auto_message' && !empty($reminder->title_note)) {
                // Direct WhatsApp text message
                try {
                    $this->whatsAppServiceEngine->processSendChatMessage([
                        'messageBody' => $reminder->title_note,
                        'contactUid' => $contact->_uid,
                    ], false, $reminder->vendors__id);

                    $sysMsg = "💬 [RELANCE WHATSAPP AUTOMATIQUE ENVOYÉE] : " . $reminder->title_note;
                    storeWhatsAppLogChatHistory([
                        'status' => 'initialize',
                        'contacts__id' => $contact->_id,
                        'vendors__id' => $reminder->vendors__id,
                        'contact_wa_id' => $contact->wa_id,
                        'is_system_message' => 1,
                        'is_incoming_message' => 0,
                        'messaged_at' => now(),
                        'message' => $sysMsg,
                        '__data' => [
                            'system_message_data' => [
                                'message' => $sysMsg
                            ]
                        ]
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Direct reminder error: ' . $e->getMessage());
                }
            } elseif ($reminder->action_type === 'template_message' && !empty($reminder->template_name)) {
                // Approved WhatsApp Template Message
                try {
                    $templateFields = data_get($reminder->__data, 'template_fields', []);
                    $requestParams = array_merge([
                        'template_name' => $reminder->template_name,
                        'template_language' => $reminder->template_language ?: 'fr',
                    ], $templateFields);

                    $request = new \App\Yantrana\Base\BaseRequestTwo($requestParams);
                    $this->whatsAppServiceEngine->sendTemplateMessageProcess($request, $contact, false, null, $reminder->vendors__id);
                    
                    $sysMsg = "📑 [RELANCE MODÈLE AUTOMATIQUE ENVOYÉE] Modèle: " . $reminder->template_name;
                    storeWhatsAppLogChatHistory([
                        'status' => 'initialize',
                        'contacts__id' => $contact->_id,
                        'vendors__id' => $reminder->vendors__id,
                        'contact_wa_id' => $contact->wa_id,
                        'is_system_message' => 1,
                        'is_incoming_message' => 0,
                        'messaged_at' => now(),
                        'message' => $sysMsg,
                        '__data' => [
                            'system_message_data' => [
                                'message' => $sysMsg
                            ]
                        ]
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Template reminder error: ' . $e->getMessage());
                }
            }

            // Mark reminder as executed
            $reminder->status = 2; // 2 = executed
            $reminder->save();
            $executedCount++;
        }

        return $executedCount;
    }
}
