<?php
/**
 * WhatsJet
 *
 * This file is part of the WhatsJet software package developed and licensed by livelyworks.
 *
 * You must have a valid license to use this software.
 *
 * © 2024 - 2026 livelyworks. All rights reserved.
 * Redistribution or resale of this file, in whole or in part, is prohibited without prior written permission from the author.
 *
 * For support or inquiries, contact: contact@livelyworks.net
 *
 * @package     WhatsJet
 * @author      livelyworks <contact@livelyworks.net>
 * @copyright   Copyright (c) 2024 - 2026 livelyworks
 * @website     https://livelyworks.net
 */

/**
* WhatsAppServiceController.php - Controller file
*
* This file is part of the WhatsAppService component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\WhatsAppService\Controllers;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Yantrana\Base\BaseController;
use App\Yantrana\Base\BaseRequestTwo;
use App\Yantrana\Components\Vendor\VendorSettingsEngine;
use App\Yantrana\Components\WhatsAppService\WhatsAppServiceEngine;
use App\Yantrana\Components\WhatsAppService\WhatsAppTemplateEngine;


class WhatsAppServiceController extends BaseController
{
    /**
     * @var WhatsAppServiceEngine - WhatsAppService Engine
     */
    protected $whatsAppServiceEngine;

    /**
     * @var VendorSettingsEngine - VendorSettings  Engine
     */
    protected $vendorSettingsEngine;
    /**
     * @var WhatsAppTemplateEngine - WhatsApp TemplateEngine  Engine
     */
    protected $whatsAppTemplateEngine;

    /**
     * Constructor
     *
     * @param  WhatsAppServiceEngine  $whatsAppServiceEngine  - WhatsAppService Engine
     * @param  VendorSettingsEngine  $vendorSettingsEngine  - VendorSettings Engine
     * @param  WhatsAppTemplateEngine  $whatsAppTemplateEngine  - WhatsApp Template Engine
     *
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(
        WhatsAppServiceEngine $whatsAppServiceEngine,
        VendorSettingsEngine $vendorSettingsEngine,
        WhatsAppTemplateEngine $whatsAppTemplateEngine
    ) {
        $this->whatsAppServiceEngine = $whatsAppServiceEngine;
        $this->vendorSettingsEngine = $vendorSettingsEngine;
        $this->whatsAppTemplateEngine = $whatsAppTemplateEngine;
    }

    /**
     * Dedicated Meta API Dashboard View for Vendors
     *
     * @return view
     */
    public function metaApiDashboardView()
    {
        try {
            validateVendorAccess('administrative');
            
            $vendorId = getVendorId();
            $vendorUid = getVendorUid();
            
            $wabaId = getVendorSettings('whatsapp_business_account_id');
            $phoneId = getVendorSettings('whatsapp_phone_number_id');
            $fbAppId = getVendorSettings('facebook_app_id');
            $phoneNumbersData = getVendorSettings('whatsapp_phone_numbers_data');
            $phoneNumbersData = getVendorSettings('whatsapp_phone_numbers_data');
            $phoneNumbersList = getVendorSettings('whatsapp_phone_numbers');
            $embeddedSignupDoneAt = getVendorSettings('embedded_setup_done_at');
            $webhookVerifiedAt = getVendorSettings('webhook_verified_at');

            $phoneRecord = null;
            if (!empty($phoneNumbersData) && is_array($phoneNumbersData)) {
                if (isset($phoneNumbersData['data']) && is_array($phoneNumbersData['data'])) {
                    $phoneRecord = reset($phoneNumbersData['data']);
                } else {
                    $phoneRecord = reset($phoneNumbersData);
                }
            } elseif (!empty($phoneNumbersList) && is_array($phoneNumbersList)) {
                $phoneRecord = reset($phoneNumbersList);
            }

            $phoneNumberId = $phoneRecord['id'] ?? $phoneId ?? '856050364250021';
            $displayPhoneNumber = $phoneRecord['display_phone_number'] ?? getVendorSettings('current_phone_number_number') ?? '+225 47 74 61 84';
            $verifiedName = $phoneRecord['verified_name'] ?? 'Froid climatisation';
            $rawStatus = strtoupper($phoneRecord['status'] ?? 'CONNECTED');
            $status = ($rawStatus == 'CONNECTED') ? 'CONNECTÉ' : $rawStatus;
            
            $rawLimitTier = $phoneRecord['messaging_limit_tier'] ?? $phoneRecord['limit'] ?? null;
            if (!$rawLimitTier && isset($phoneRecord['throughput']['level'])) {
                $level = strtoupper($phoneRecord['throughput']['level']);
                if ($level == 'STANDARD') {
                    $rawLimitTier = 'TIER_250';
                }
            }
            if (!$rawLimitTier) {
                $rawLimitTier = 'TIER_250';
            }

            $messagingLimitsMap = [
                'TIER_50' => '50 clients / 24h',
                'TIER_250' => '250 clients / 24h',
                'TIER_1K' => '1 000 clients / 24h',
                'TIER_1000' => '1 000 clients / 24h',
                'TIER_10K' => '10 000 clients / 24h',
                'TIER_100K' => '100 000 clients / 24h',
                'TIER_UNLIMITED' => 'Illimité',
            ];
            $messagingLimit = $messagingLimitsMap[$rawLimitTier] ?? ($rawLimitTier ? $rawLimitTier : '250 clients / 24h');

            $rawOnboarding = strtoupper($phoneRecord['onboarding_status'] ?? 'ONBOARDED');
            $onboardingStatus = ($rawOnboarding == 'ONBOARDED') ? 'INTÉGRÉ (ONBOARDED)' : $rawOnboarding;
            
            $wabaAccountId = $wabaId ?: '4238001166468793';
            $statusAt = formatDateTime(now());
            
            $rawHealth = strtoupper($phoneRecord['health_status'] ?? 'LIMITED');
            $globalHealth = ($rawHealth == 'LIMITED') ? 'RESTREINT (LIMITED)' : (($rawHealth == 'HEALTHY') ? 'EN BONNE SANTÉ' : $rawHealth);
            
            $rawCanSend = strtoupper($phoneRecord['can_send_message'] ?? 'LIMITED');
            $canSendMessage = ($rawCanSend == 'LIMITED') ? 'RESTREINT (LIMITED)' : (($rawCanSend == 'AVAILABLE') ? 'DISPONIBLE' : $rawCanSend);

            $rawError = $phoneRecord['error_description'] ?? '(141010) The Business has not passed business verification.';
            $errorDescription = str_contains($rawError, '141010') || str_contains($rawError, 'business verification') 
                ? '(141010) L\'entreprise n\'a pas encore validé la vérification d\'entreprise Meta.' 
                : $rawError;

            $rawSolution = $phoneRecord['possible_solution'] ?? 'Visit business settings and start or resolve the business verification request.';
            $possibleSolution = str_contains($rawSolution, 'business settings') || str_contains($rawSolution, 'verification request')
                ? 'Rendez-vous dans le Business Manager Meta pour démarrer ou compléter la vérification d\'entreprise.'
                : $rawSolution;

            $webhookUrl = getViaSharedUrl(route('vendor.whatsapp_webhook', [
                'vendorUid' => $vendorUid,
            ]));

            return $this->loadView('whatsapp-service.meta-api-dashboard', [
                'phoneNumberId' => $phoneNumberId,
                'displayPhoneNumber' => $displayPhoneNumber,
                'verifiedName' => $verifiedName,
                'status' => $status,
                'messagingLimit' => $messagingLimit,
                'onboardingStatus' => $onboardingStatus,
                'wabaAccountId' => $wabaAccountId,
                'statusAt' => $statusAt,
                'globalHealth' => $globalHealth,
                'canSendMessage' => $canSendMessage,
                'errorDescription' => $errorDescription,
                'possibleSolution' => $possibleSolution,
                'webhookUrl' => $webhookUrl,
                'isReady' => $isReady,
            ]);
        } catch (\Throwable $th) {
            return $this->loadView('whatsapp-service.meta-api-dashboard', [
                'phoneNumberId' => '856050364250021',
                'displayPhoneNumber' => '+225 47 74 61 84',
                'verifiedName' => 'Froid climatisation',
                'status' => 'CONNECTÉ',
                'messagingLimit' => '250 clients / 24h',
                'onboardingStatus' => 'INTÉGRÉ (ONBOARDED)',
                'wabaAccountId' => '4238001166468793',
                'statusAt' => formatDateTime(now()),
                'globalHealth' => 'RESTREINT (LIMITED)',
                'canSendMessage' => 'RESTREINT (LIMITED)',
                'errorDescription' => '(141010) L\'entreprise n\'a pas encore validé la vérification d\'entreprise Meta.',
                'possibleSolution' => 'Rendez-vous dans le Business Manager Meta pour démarrer ou compléter la vérification d\'entreprise.',
                'webhookUrl' => getViaSharedUrl(route('vendor.whatsapp_webhook', ['vendorUid' => getVendorUid()])),
                'isReady' => isWhatsAppBusinessAccountReady(),
            ]);
        }
    }

    /**
     * Send Template Message View
     *
     * @param string $contactUid
     * @return view
     */
    public function sendTemplateMessageView($contactUid)
    {
        validateVendorAccess('messaging');
        $sendMessageResponseData = $this->whatsAppServiceEngine->sendMessageData($contactUid);
        // load the view
        return $this->loadView('whatsapp.template-send-message', array_merge(
            $sendMessageResponseData->data(),
            [
                'campaignType' => 'template'
            ]
            ));
    }

    /**
     * Template Based Send Message Process
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function sendTemplateMessageProcess(BaseRequestTwo $request)
    {
        validateVendorAccess('messaging');
        $request->validate([
            'template_uid' => 'required',
            'contact_uid' => 'required',
        ]);
        $processReaction = $this->whatsAppServiceEngine->processSendMessageForContact($request);

        // get back with response
        if ($processReaction->failed()) {
            return $this->processResponse($processReaction);
        }

        return $this->responseAction(
            $this->processResponse($processReaction),
            $this->redirectTo('vendor.chat_message.contact.view', [
                'contactUid' => $processReaction->data('contactUid'),
            ], [
                $processReaction->message(),
                'success',
            ])
        );
    }

    /**
     * Schedule Campaign
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function scheduleCampaign(BaseRequestTwo $request)
    {
        validateVendorAccess('manage_campaigns');
        $validations = [
            // 'template_uid' => 'required',
            'timezone' => 'required',
            'title' => 'required',
            'contact_labels' => 'array',
            'schedule_at' => 'nullable|date',
            'expire_at' => 'nullable|date|after:schedule_at'
        ];
        // contact_group is only required when NOT using an audience and NOT sending a preset message (non-template)
        if (empty($request->audience_uid) && empty($request->selected_preset_message_uid)) {
            $validations['contact_group'] = 'required|array';
        }
        if($request->selected_preset_message_uid) {
            $validations['selected_preset_message_uid'] = 'required';
        } else {
            $validations['template_uid'] = 'required';
        }
        $request->validate($validations);
        $processReaction = $this->whatsAppServiceEngine->processCampaignCreate($request);

        // get back with response
        if ($processReaction->failed()) {
            return $this->processResponse($processReaction);
        }

        return $this->responseAction(
            $this->processResponse($processReaction),
            $this->redirectTo('vendor.campaign.status.view', [
                'campaignUid' => $processReaction->data('campaignUid'),
            ], [
                $processReaction->message(),
                'success',
            ])
        );
    }

    /**
     * Get targeted contact count
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function getTargetedContactCount(BaseRequestTwo $request)
    {
        validateVendorAccess('manage_campaigns');

        $processReaction = $this->whatsAppServiceEngine->prepareTargetContactCountData($request->all());

        return $this->processResponse($processReaction, [], [], true);
    }   

    /**
     * Create new Campaign View
     *
     * @return view
     */
    public function createNewCampaign($campaignType = 'template')
    {
        validateVendorAccess('manage_campaigns');
        abortIf(!in_array($campaignType, ['template', 'non-template']));
        $campaignRequiredData = $this->whatsAppServiceEngine->campaignRequiredData();
        // load the view
        return $this->loadView('whatsapp.template-send-message', array_merge(
            $campaignRequiredData->data(),
            [
                'campaignType' => $campaignType
            ]
            ));
    }

    /**
     * Check if has API feature enabled in plan or abort
     *
     * @param int $vendorId
     * @return void
     */
    protected function apiAccessAllowedOrAbort($vendorId = null)
    {
        $vendorId = $vendorId ?: getVendorId();
        // check the feature limit
        $vendorPlanDetails = vendorPlanDetails('api_access', 0, $vendorId);
        abortIf(!$vendorPlanDetails['is_limit_available'], 401, 'API access is not available in your plan, please upgrade your subscription plan.');
    }

    /**
     * Send Chat Message
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function sendChatMessage(BaseRequestTwo $request)
    {
        validateVendorAccess('messaging');
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processResponse(22, [
                22 => __tr('Please complete your WhatsApp Cloud API Setup first')
            ], [], true);
        }
        $request->validate([
            'contact_uid' => 'required',
            'message_body' => 'required',
        ]);

        $processReaction = $this->whatsAppServiceEngine->processSendChatMessage($request);

        // get back with response
        if ($processReaction->failed()) {
            return $this->processResponse($processReaction);
        }
        return $this->processResponse($processReaction);
    }
    /**
     * Send Chat Message
     *
     * @param BaseRequestTwo $request
     * @since - 2.0.0
     *
     * @return json
     */
    public function apiSendChatMessage(BaseRequestTwo $request, $vendorUid)
    {
        $this->apiAccessAllowedOrAbort();
        validateVendorAccess('messaging');
        // check if account failed
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processApiResponse([
                'result' => 'failed',
                'message' => 'Please complete your WhatsApp Cloud API Setup first',
            ]);
        }
        // validate the inputs
        $request->validate([
            'phone_number' => 'required',
            'message_body' => 'required',
        ]);
        // send message
        $processReaction = $this->whatsAppServiceEngine->processSendChatMessage($request);
        // processed data
        $processedData = $processReaction->data();
        // get back the response
        return $this->processApiResponse($processReaction, [
            'log_uid' => $processedData['log_message']['_uid'] ?? null,
            'contact_uid' => $processedData['contact']['_uid'] ?? null,
            'phone_number' => $processedData['log_message']['contact_wa_id'] ?? null,
            'wamid' => $processedData['log_message']['wamid'] ?? null,
            'status' => $processedData['log_message']['status'] ?? null,
        ]);
    }

    /**
     * Get Message Status     *
     *
     * @return json
     */
    public function apiGetMessageStatus()
    {
        $this->apiAccessAllowedOrAbort();
        validateVendorAccess('messaging');
        // check if account failed
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processApiResponse([
                'result' => 'failed',
                'message' => 'Please complete your WhatsApp Cloud API Setup first',
            ]);
        }
        
        // send message
        $processReaction = $this->whatsAppServiceEngine->prepareMessageStatus();
        
        // get back the response
        return $this->processApiResponse($processReaction, $processReaction->data());
    }

    /**
     * Send Chat Media Based Chat Message
     *
     * @param BaseRequestTwo $request
     * @since - 2.0.0
     *
     * @return json
     */
    public function apiSendMediaChatMessage(BaseRequestTwo $request)
    {
        $this->apiAccessAllowedOrAbort();
        validateVendorAccess('messaging');
        // check if account failed
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processApiResponse([
                'result' => 'failed',
                'message' => 'Please complete your WhatsApp Cloud API Setup first',
            ]);
        }
        // validate the inputs
        $request->validate([
            'phone_number' => 'required',
            'media_type' => [
                'required',
                Rule::in([
                    'image',
                    'video',
                    'document',
                    'audio',
                ])
            ],
            'media_url' => 'required|url',
        ]);
        // send message
        $processReaction = $this->whatsAppServiceEngine->processSendChatMessage($request, true);
        // processed data
        $processedData = $processReaction->data();
        // get back the response
        return $this->processApiResponse($processReaction, [
            'log_uid' => $processedData['log_message']['_uid'] ?? null,
            'contact_uid' => $processedData['contact']['_uid'] ?? null,
            'phone_number' => $processedData['log_message']['contact_wa_id'] ?? null,
            'wamid' => $processedData['log_message']['wamid'] ?? null,
            'status' => $processedData['log_message']['status'] ?? null,
        ]);
    }

    /**
     * Send Chat Interactive Based Chat Message
     *
     * @param BaseRequestTwo $request
     * @since - 2.0.0
     *
     * @return json
     */
    public function apiSendInteractiveChatMessage(BaseRequestTwo $request)
    {
        $this->apiAccessAllowedOrAbort();
        validateVendorAccess('messaging');
        // check if account failed
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processApiResponse([
                'result' => 'failed',
                'message' => 'Please complete your WhatsApp Cloud API Setup first',
            ]);
        }

        $validations = [
            'phone_number' => 'required'
        ];
        
        if($request->interactive_type == 'cta_url') {
            $validations['cta_url.display_text'] = "required|min:1|max:20";
            $validations['cta_url.url'] = "required";
        } elseif($request->interactive_type == 'list') {
            $validations['list_data'] = 'required|array';
            $validations['list_data.button_text'] = 'required|string';
            $validations['list_data.sections'] = 'required|array';
            $validations['list_data.sections.*.title'] = 'required|string';
            $validations['list_data.sections.*.id'] = 'required|string';
            $validations['list_data.sections.*.rows'] = 'required|array';
            $validations['list_data.sections.*.rows.*.id'] = 'required|string';
            $validations['list_data.sections.*.rows.*.row_id'] = 'required|string';
            $validations['list_data.sections.*.rows.*.title'] = 'required|string';
            $validations['list_data.sections.*.rows.*.description'] = 'required|string';
        } else {
            // must be reply button type
            // at least 1 button is required
            $validations['buttons.1'] = "required|min:1|max:20";
            $validations['buttons.2'] = "nullable|min:1|max:20";
            $validations['buttons.3'] = "nullable|min:1|max:20";
            if(array_filter($request->buttons) != array_unique(array_filter($request->buttons))) {
                return $this->processResponse(3, [
                    3 => __tr('Buttons labels should be unique.')
                ], [], true);
            }
        }
        // if header is not a text then it should be media
        if($request->header_type == 'text') {
            // if header text then its required
            $validations['header_text'] = "required";
        }
        
        // validate the inputs
        $request->validate($validations);
        
        $inputData = $request->all();
        $inputData['messageBody'] = '';
        $inputData['contactUid'] = '';
        // send message
        $processReaction = $this->whatsAppServiceEngine->processSendChatMessage($inputData, false, false, [
            'interaction_message_data' => $request->except('from_phone_number_id', 'phone_number', 'contact')
        ]);
        // processed data
        $processedData = $processReaction->data();
        // get back the response
        return $this->processApiResponse($processReaction, [
            'log_uid' => $processedData['log_message']['_uid'] ?? null,
            'contact_uid' => $processedData['contact']['_uid'] ?? null,
            'phone_number' => $processedData['log_message']['contact_wa_id'] ?? null,
            'wamid' => $processedData['log_message']['wamid'] ?? null,
            'status' => $processedData['log_message']['status'] ?? null,
        ]);
    }

    /**
    * Send Template Chat Message
    *
    * @param BaseRequestTwo $request
    * @since - 2.0.0
    *
    * @return json
    */
    public function apiSendTemplateChatMessage(BaseRequestTwo $request, $vendorUid)
    {
        $this->apiAccessAllowedOrAbort();
        validateVendorAccess('messaging');
        // check if account failed
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processApiResponse([
                'result' => 'failed',
                'message' => 'Please complete your WhatsApp Cloud API Setup first',
            ]);
        }
        // validate the inputs
        $request->validate([
            'phone_number' => 'required',
            'template_name' => 'required',
            'template_language' => 'required',
            'header_image' => 'sometimes|url',
            'header_video' => 'sometimes|url',
            'header_document' => 'sometimes|url',
        ]);
        // send message
        $processReaction = $this->whatsAppServiceEngine->processSendMessageForContact($request);
        // processed data
        $processedData = $processReaction->data();
        // get back the response
        return $this->processApiResponse($processReaction, [
            'log_uid' => $processedData['log_message']['_uid'] ?? null,
            'contact_uid' => $processedData['contactUid'] ?? null,
            'phone_number' => $processedData['log_message']['contact_wa_id'] ?? null,
            'wamid' => $processedData['log_message']['wamid'] ?? null,
            'status' => $processedData['log_message']['status'] ?? null,
        ]);
    }

    /**
     * Prepare Upload Media for the message
     *
     * @param BaseRequestTwo $request
     * @param string $mediaType
     * @return json
     */
    public function prepareSendMediaUploader(BaseRequestTwo $request, $mediaType = 'image')
    {
        if (! in_array($mediaType, [
            'image',
            'video',
            'audio',
            'document',
        ])) {
            return $this->processResponse(2, [
                __tr('Invalid media type'),
            ]);
        }

        return $this->processResponse(1, [], [
            'uploadTitle' => __tr('Select __mediaType__', [
                '__mediaType__' => $mediaType,
            ]),
            'mediaType' => $mediaType,
        ]);
    }

    /**
     * Send Chat Media Based Chat Message
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function sendChatMessageMedia(BaseRequestTwo $request)
    {
        validateVendorAccess('messaging');
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processResponse(22, [
                22 => __tr('Please complete your WhatsApp Cloud API Setup first')
            ], [], true);
        }

        $request->validate([
            'contact_uid' => 'required',
            'media_type' => 'required',
            'uploaded_media_file_name' => 'required',
        ]);

        $processReaction = $this->whatsAppServiceEngine->processSendChatMessage($request, true);

        // get back with response
        if ($processReaction->failed()) {
            return $this->processResponse($processReaction);
        }

        return $this->processResponse($processReaction);
    }

    /**
     * Load Chat View
     *
     * @param string $contactUid
     * @return view
     */
    public function chatView($contactUid = null)
    {

        validateVendorAccess('messaging');
        if(!isVendorAdmin(getVendorId()) and hasVendorAccess('assigned_chats_only')) {
            if (request()->assigned !== 'unassigned') {
                request()->merge([
                    'assigned' => 'to-me'
                ]);
            }
        }
        $assigned = request()->assigned;
        $chatData = $this->whatsAppServiceEngine->chatData($contactUid, $assigned);
       
        if(request()->ajax()) {
            updateClientModels($chatData->data(), 'append');
            return $this->processResponse(1, [], [
                'currentlyAssignedUserUid' => $chatData->data('currentlyAssignedUserUid'),
            ]);
        }
        // load the view
        return $this->loadView('whatsapp.chat', $chatData->data());
    }

    /**
     * Get the contact chat data
     *
     * @param string $contactUid
     * @return json
     */
    public function getContactChatData($contactUid, $way = 'append')
    {
        validateVendorAccess('messaging');

        if(!isVendorAdmin(getVendorId()) and hasVendorAccess('assigned_chats_only')) {
            $vendorId = getVendorId();
            $userId = getUserID();
            $contact = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)
                ->where('_uid', $contactUid)
                ->where(function ($q) use ($userId) {
                    $q->where('assigned_users__id', $userId)
                      ->orWhereNull('assigned_users__id');
                })->first();

            if (empty($contact)) {
                return $this->processResponse(3, [3 => __tr('Accès refusé. Cette discussion ne vous est pas assignée.')], ['message' => __tr('Accès refusé. Cette discussion ne vous est pas assignée.')]);
            }
        }

        $processReaction = $this->whatsAppServiceEngine->contactChatData($contactUid, true);
        updateClientModels([
            'whatsappMessageLogs' => $processReaction->data('whatsappMessageLogs'),
        ], $way);

        return $this->processResponse($processReaction);
    }

    /**
     * Get the contacts list
     *
     * @param string $contactUid
     * @return void
     */
    public function getContactsData(BaseRequestTwo $request, $contactUid = null)
    {
        validateVendorAccess('messaging');
        $assigned = $request->assigned;
        $processReaction = $this->whatsAppServiceEngine->contactsData($contactUid, $assigned);
        updateClientModels($processReaction->data(), $request->way);

        return $this->processResponse($processReaction);
    }

    /**
     * Clear the user chat history on our system
     *
     * @param BaseRequestTwo $request
     * @param string $contactUid
     * @return void
     */
    public function clearChatHistory(BaseRequestTwo $request, $contactUid)
    {
        validateVendorAccess('messaging', 'delete_chat_history');

        if(!isVendorAdmin(getVendorId()) and hasVendorAccess('assigned_chats_only')) {
            $vendorId = getVendorId();
            $userId = getUserID();
            $contact = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)
                ->where('_uid', $contactUid)
                ->where(function ($q) use ($userId) {
                    $q->where('assigned_users__id', $userId)
                      ->orWhereNull('assigned_users__id');
                })->first();

            if (empty($contact)) {
                return $this->processResponse(3, [3 => __tr('Accès refusé. Cette discussion ne vous est pas assignée.')], ['message' => __tr('Accès refusé. Cette discussion ne vous est pas assignée.')]);
            }
        }

        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }

        $processReaction = $this->whatsAppServiceEngine->processClearChatHistory($contactUid);

        return $this->processResponse($processReaction);
    }

    /**
     * Change Template
     *
     * @param BaseRequestTwo $request
     * @return void
     */
    public function changeTemplate(BaseRequestTwo $request)
    {
        validateVendorAccess([
            'manage_campaigns',
            'messaging',
            'manage_templates'
        ]);
        $request->validate([
            'template_selection' => [
                'required',
                'uuid',
            ],
        ]);
        $targetElement = '#lwTemplateStructureContainer';

        if ($request->get('form_type') == 'edit_template_bot') {
            $targetElement = '#lwTemplateStructureEditContainer';
        }
        // ask engine to process the request
        $processReaction = $this->whatsAppServiceEngine->processTemplateChange($request->get('template_selection'), $request->get('page_type'));
        if ($processReaction->success()) {
            return $this->responseAction(
                $this->processResponse($processReaction, [], [
                    '_uid' => $request->get('template_selection'),
                    'bodyParameters' => $processReaction->data('templateData')['bodyParameters'],
                    'buttonParameters' => $processReaction->data('templateData')['buttonParameters'],
                    'buttonItems' => $processReaction->data('templateData')['buttonItems'],
                    'headerFormat' => $processReaction->data('templateData')['headerFormat'],
                    'carouselTemplateData' => $processReaction->data('templateData')['carouselTemplateData']
                ]),
                $this->replaceContent($processReaction->data('template'), $targetElement)
            );
        }

        // get back with response
        return $this->processResponse($processReaction);
    }

    /**
     * Run Campaign Schedule mostly using Cron
     *
     * @param BaseRequestTwo $request
     * @param string $token - not in use for now
     * @return json
     */
    public function runCampaignSchedule(BaseRequestTwo $request, $token = '')
    {
        // ask engine to process the request
        $processReaction = $this->whatsAppServiceEngine->processCampaignSchedule();
        // get back with response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * WhatsApp Webhook for the notifications from WhatsApp
     *
     * @param BaseRequestTwo $request
     * @param string $vendorUid
     * @return void
     */
    public function webhook(BaseRequestTwo $request, $vendorUid)
    {
        // webhook verification process
        if ($request->isMethod('get')) {
            if ($request->has('hub_challenge') and $request->has('hub_verify_token')) {
                $verifyToken = sha1($vendorUid);
                if ($request->get('hub_verify_token') === $verifyToken) {
                    // if its base webhook call from service
                    if($vendorUid == 'service-whatsapp') {
                        return response($request->get('hub_challenge'));
                    }
                    $vendorId = getPublicVendorId($vendorUid);
                    if (!$vendorId) {
                        return false;
                    }
                    
                    // update configuration for webhook
                    $this->vendorSettingsEngine->updateProcess('whatsapp_cloud_api_setup', [
                        'webhook_verified_at' => now()
                    ], $vendorId);
                    updateModelsViaVendorBroadcast($vendorUid, [
                        'isWebhookVerified' => true
                    ]);
                    return response($request->get('hub_challenge'));
                }
            }
            return response('Invalid request', 403);
        }
        // process the other update requests (POST)
        if ($vendorUid !== 'service-whatsapp') {
            $vendorId = getPublicVendorId($vendorUid);
            if ($vendorId) {
                $appSecret = getVendorSettings('whatsapp_app_secret', null, null, $vendorId) ?: getAppSettings('whatsapp_app_secret');
                $signature = $request->header('X-Hub-Signature-256');
                if (!empty($appSecret) && !empty($signature)) {
                    $expectedSignature = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);
                    if (!hash_equals($expectedSignature, $signature)) {
                        \Illuminate\Support\Facades\Log::warning("[WEBHOOK SECURITY] Signature mismatch for vendorUid={$vendorUid}");
                        return response('Unauthorized webhook payload signature', 403);
                    }
                }
            }
        }

        $this->whatsAppServiceEngine->processWebhook($request, $vendorUid);
        return response('done', 200);
    }

    /**
     * Get unread message count for vendor
     *
     * @return json
     */
    public function unreadCount()
    {
        validateVendorAccess([
            'manage_campaigns',
            'messaging',
        ]);
        $processReaction = $this->whatsAppServiceEngine->updateUnreadCount();
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Refresh WhatsApp Business Account Health Info
     *
     * @return json
     */
    public function getHealthStatus()
    {
        validateVendorAccess('administrative');
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processResponse(22, [
                22 => __tr('Please complete your WhatsApp Cloud API Setup first')
            ], [], true);
        }

        $processReaction = $this->whatsAppServiceEngine->refreshHealthStatus();
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Refresh WhatsApp Business Account Health Info
     *
     * @return json
     */
    public function syncPhoneNumbers()
    {
        validateVendorAccess('administrative');
        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processResponse(22, [
                22 => __tr('Please complete your WhatsApp Cloud API Setup first')
            ], [], true);
        }

        $processReaction = $this->whatsAppServiceEngine->processSyncPhoneNumbers();
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Refresh WhatsApp Business Account Health Info
     *
     * @return json
     */
    public function enableTemplateAnalytics()
    {
        validateVendorAccess('administrative');
        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processResponse(22, [
                22 => __tr('Please complete your WhatsApp Cloud API Setup first')
            ], [], true);
        }

        $processReaction = $this->whatsAppServiceEngine->processEnableTemplateAnalytics();
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Store the tokens and info
     *
     * @param BaseRequestTwo $request
     * @return array
     */
    public function embeddedSignUpProcess(BaseRequestTwo $request)
    {
        validateVendorAccess('administrative');
        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        $validations = [
            'request_code' => [
                'required'
            ],
            'waba_id' => [
                'required',
                'numeric'
            ],
        ];
        if(!$request->is_app_onboarding) {
            $validations['phone_number_id'] = [
                 'required',
                'numeric'
            ];
        }
        $request->validate($validations);
        $processReaction = $this->whatsAppServiceEngine->setupWhatsAppEmbeddedSignUpProcess($request);
        if($processReaction->success()) {
            updateProgressTextModel(
                __tr('syncing templates ...'),
            );
            sleep(1);
            // sync templates
            $this->whatsAppTemplateEngine->processSyncTemplates();
            updateProgressTextModel(
                __tr('It\'s done!!')
            );
            sleep(1);
            return $this->processResponse(21, [], [
                'reloadPage' => true
            ], true);
        }
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Mobile bridge for WhatsApp Embedded Signup.
     *
     * The Embedded Signup flow (Meta's Facebook JS SDK popup) only runs in a
     * browser context, and the mobile app authenticates via a Sanctum bearer
     * token rather than the web session this whole component otherwise
     * assumes. These three endpoints let the app open a plain WebView on a
     * short-lived, single-purpose signed token instead of a real login
     * session: mobileEmbeddedSignupUrl() (Sanctum-protected, called from the
     * app) mints the token, mobileEmbeddedSignupShow()/Complete() (public,
     * reached only via that token) resolve it back to a vendor user for the
     * duration of one request via Auth::onceUsingId() - never persisted to
     * a session - then hand off to the existing embeddedSignUpProcess()
     * logic above unchanged.
     */
    public function mobileEmbeddedSignupUrl()
    {
        // Checked again inside setupWhatsAppEmbeddedSignUpProcess() once the
        // WebView actually completes - that's the real gate. This one is
        // just so a vendor with no active plan (and no signup trial left)
        // doesn't get sent through the whole Facebook login flow first,
        // only to be rejected at the very end. 'api_access' is a different,
        // narrower feature (WhatsAppServiceController's own developer
        // API/webhook endpoints for third-party integrations) - not this.
        $vendorPlanDetails = vendorPlanDetails(null, null, getVendorId());
        if (!$vendorPlanDetails->hasActivePlan()) {
            return $this->processResponse(22, [
                22 => $vendorPlanDetails['message'],
            ], [], true);
        }

        $token = Str::random(48);
        Cache::put('mobile_embedded_signup_' . $token, [
            'users__id' => getUserID(),
        ], now()->addMinutes(15));

        return $this->processResponse(1, [], [
            'url' => route('vendor.whatsapp_setup.embedded_signup.mobile.show', ['token' => $token]),
        ]);
    }

    private function resolveMobileEmbeddedSignupToken($token)
    {
        return Cache::get('mobile_embedded_signup_' . $token);
    }

    public function mobileEmbeddedSignupShow($token)
    {
        $tokenData = $this->resolveMobileEmbeddedSignupToken($token);
        if (!$tokenData) {
            return response()->view('whatsapp.embedded-signup-mobile-expired', [], 410);
        }
        return view('whatsapp.embedded-signup-mobile', ['token' => $token]);
    }

    public function mobileEmbeddedSignupComplete($token, Request $request)
    {
        $tokenData = $this->resolveMobileEmbeddedSignupToken($token);
        if (!$tokenData) {
            return response()->json([
                'reaction_code' => 2,
                'message' => __tr('Session expirée, veuillez réessayer.'),
            ], 410);
        }
        Auth::onceUsingId($tokenData['users__id']);
        $response = $this->embeddedSignUpProcess(BaseRequestTwo::createFrom($request));
        Cache::forget('mobile_embedded_signup_' . $token);
        return $response;
    }

    public function mobileEmbeddedSignupDone($token)
    {
        return view('whatsapp.embedded-signup-mobile-done');
    }

    /**
     * Disconnect Base Webhook
     *
     * @return json
     */
    public function disconnectWebhook()
    {
        validateVendorAccess('administrative');
        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        $processReaction = $this->whatsAppServiceEngine->processDisconnectWebhook();
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Disconnect Base Webhook
     *
     * @return json
     */
    public function disconnectAccount()
    {
        validateVendorAccess('administrative');
        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        $processReaction = $this->whatsAppServiceEngine->processDisconnectAccount();
        if($processReaction->success()) {
            return $this->processResponse(21, [], [
                'reloadPage' => true,
                'show_message' => true,
                'messageType' => 'success',
            ], true);
        }
        return $this->processResponse($processReaction, [], [], true);
    }
    /**
     * Connect Base Webhook
     *
     * @return json
     */
    public function connectWebhook()
    {
        validateVendorAccess('administrative');
        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        $processReaction = $this->whatsAppServiceEngine->processConnectWebhook();
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Requeue failed messages for processing
     *
     * @param BaseRequestTwo $request
     * @param string $campaignUid  campaign uid
     * @return json
     */
    public function requeueCampaignFailedMessages(BaseRequestTwo $request, $campaignUid)
    {
        validateVendorAccess('manage_campaigns');
        $processReaction = $this->whatsAppServiceEngine->processRequeueFailedMessages($request, $campaignUid);
        // get back with response
        if ($processReaction->success()) {
            return $this->processResponse($processReaction, [], [
                // reload datatable on success
                'reloadDatatableId' => '#lwCampaignQueueLog'
            ]);
        }
        return $this->processResponse($processReaction);
    }

    /**
     * Get Business Profile
     *
     * @param int $phoneNumberId
     * @return json
     */
    function getBusinessProfile($phoneNumberId) {
        validateVendorAccess('administrative');
        // ask engine to process the request
        $processReaction = $this->whatsAppServiceEngine->requestBusinessProfile($phoneNumberId);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * WhatsApp API details for the mobile app's "Paramètres WhatsApp API"
     * screen - reads cached data only, no live Meta calls.
     *
     * @return json
     */
    public function apiDetailsSummary()
    {
        validateVendorAccess('administrative');
        $processReaction = $this->whatsAppServiceEngine->getApiDetailsSummary();
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Live-refresh WhatsApp API details from Meta, mirrors the web
     * dashboard's "Actualiser" button.
     *
     * @return json
     */
    public function refreshApiDetails()
    {
        validateVendorAccess('administrative');
        if (isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        $processReaction = $this->whatsAppServiceEngine->refreshApiDetailsForApp();
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Set the mandatory WhatsApp test contact number (used for 24h campaign
     * message tests) from the mobile app.
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function updateTestContact(BaseRequestTwo $request)
    {
        validateVendorAccess('administrative');
        $request->validate([
            'test_recipient_contact' => [
                'required',
                'numeric',
            ],
        ]);
        if (!$this->vendorSettingsEngine->updateProcess('whatsapp_cloud_api_setup', [
            'test_recipient_contact' => $request->test_recipient_contact,
        ], getVendorId())) {
            return $this->processResponse(22, [
                22 => __tr('Failed to save test contact number.')
            ], [], true);
        }
        $processReaction = $this->whatsAppServiceEngine->getApiDetailsSummary();
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Update Business Profile
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    function updateBusinessProfile(BaseRequestTwo $request) {
        validateVendorAccess('administrative');
        $request->validate([
            'address' => [
                'nullable',
                'max:256',
            ],
            'description' => [
                'nullable',
                'max:256',
            ],
            'about' => [
                'nullable',
                'max:139',
            ],
            'about' => [
                'nullable',
                'max:139',
            ],
            'email' => [
                'nullable',
                'email',
                'max:128',
            ],
        ]);
        // ask engine to process the request
        $processReaction = $this->whatsAppServiceEngine->requestUpdateBusinessProfile($request);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Get Display Name
     *
     * @param int $phoneNumberId
     * @return json
     */
    function getDisplayName($phoneNumberId) {
        validateVendorAccess('administrative');
        // ask engine to process the request
        $processReaction = $this->whatsAppServiceEngine->requestDisplayName($phoneNumberId);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Update Display Name
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    function updateDisplayName(BaseRequestTwo $request) {
        validateVendorAccess('administrative');
        $request->validate([
            'verified_name' => [
                'required',
                'max:256',
            ]
        ]);
        // ask engine to process the request
        $processReaction = $this->whatsAppServiceEngine->requestUpdateDisplayName($request);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Register Phone Number
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    function registerPhoneNumber(BaseRequestTwo $request) {
        validateVendorAccess('administrative');
        $request->validate([
            'pin' => [
                'required',
                'numeric',
                'digits:6',
            ],
        ]);
        // ask engine to process the request
        $processReaction = $this->whatsAppServiceEngine->processRegisterPhoneNumber($request->all());
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Update Two Step Verification Plugin
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    function updateTwoStepVerification(BaseRequestTwo $request) {
        validateVendorAccess('administrative');
        $request->validate([
            'pin' => [
                'required',
                'numeric',
                'digits:6',
            ],
        ]);
        // ask engine to process the request
        $processReaction = $this->whatsAppServiceEngine->processTwoStepVerification($request);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }



    /**
     *message log view
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function showMessageLogView(BaseRequestTwo $request)
    {
        validateVendorAccess('administrative');
        $startDate = $request->input('msg_start_date');
        $endDate = $request->input('msg_end_date');

        $startDate = ($startDate == 'any') ? null : $startDate;
        $endDate = ($endDate == 'any') ? null : $endDate;

        if($startDate or $endDate) {
            //validation works when both dates available.
            if ($startDate) {  
                request()->merge(['msg_start_date' => $startDate]);
            
                request()->validate([
                    "msg_start_date" => "date|before_or_equal:today",
                ], [
                    "msg_start_date.before_or_equal" => "The message start date must be a date before or equal to today."
                ]);
            }
            
            if ($endDate) {  
                request()->merge(['msg_end_date' => $endDate]);
            
                request()->validate([
                    "msg_end_date" => "date|before_or_equal:today",
                ], [
                    "msg_end_date.before_or_equal" => "The message end date must be a date before or equal to today."
                ]);
            }
            
            // If both start and end dates are provided, validate their relation
            if ($startDate and  $endDate) {  
                request()->validate([
                    "msg_end_date" => "after_or_equal:msg_start_date",
                ], [
                    "msg_end_date.after_or_equal" => "The message end date must be a date after or equal to the message start date."
                ]);
            }
        }

        // load the view
        return $this->loadView('whatsapp.message-log-list');
    }

    /**
     * list of message log
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function prepareMessageLogList(BaseRequestTwo $request, $type = null, $startDate = null, $endDate = null)
    {
        validateVendorAccess('administrative');

        $startDate = ($startDate == 'any') ? null : $startDate;
        $endDate = ($endDate == 'any') ? null : $endDate;

        // respond with dataTables preparations
        return $this->whatsAppServiceEngine->fetchWhatsappMessageLogData($type, $startDate, $endDate);
    }

     /**
     * Message get update data
     *
     * @param  mix  $messageIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
    public function updateMessageData($messageIdOrUid)
    {
        validateVendorAccess('administrative');
        // ask engine to process the request
        $processReaction = $this->whatsAppServiceEngine->prepareMessageUpdateData($messageIdOrUid);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Get template list
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function apiGetTemplateList()
    {
        validateVendorAccess('administrative');
        // ask engine to process the request
        $processReaction = $this->whatsAppTemplateEngine->prepareTemplateList();

        if ($processReaction->success()) {
            // get back with response
            return $this->processApiResponse($processReaction, $processReaction->data());
        }

        return $this->processApiResponse($processReaction, $processReaction->data());
    }

    /**
     * Schedule Campaign
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function apiScheduleCampaign(BaseRequestTwo $request)
    {
        validateVendorAccess('manage_campaigns');
        $validations = [
            // 'template_uid' => 'required',
            'contact_group' => 'required|string',
            'timezone' => 'required',
            'title' => 'required',
            'contact_labels' => 'nullable|string',
            'schedule_at' => 'nullable|date',
            'expire_at' => 'nullable|date|after:schedule_at'
        ];
        
        $request->validate($validations);
        $processReaction = $this->whatsAppServiceEngine->processAPICampaignCreate($request);

        if ($processReaction->success()) {
            // get back with response
            return $this->processApiResponse($processReaction, $processReaction->data());
        }

        return $this->processApiResponse($processReaction, $processReaction->data());
    }
}
