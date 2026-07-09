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
 * DashboardEngine.php - Main component file
 *
 * This file is part of the Dashboard component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Dashboard;

use Illuminate\Support\Carbon;
use App\Yantrana\Base\BaseEngine;
use App\Yantrana\Components\Vendor\VendorEngine;
use App\Yantrana\Components\User\Repositories\UserRepository;
use App\Yantrana\Components\Vendor\Repositories\VendorRepository;
use App\Yantrana\Components\Contact\Repositories\ContactRepository;
use App\Yantrana\Components\BotReply\Repositories\BotFlowRepository;
use App\Yantrana\Components\BotReply\Repositories\BotReplyRepository;
use App\Yantrana\Components\Campaign\Repositories\CampaignRepository;
use App\Yantrana\Components\Contact\Repositories\ContactGroupRepository;
use App\Yantrana\Components\Contact\Repositories\GroupContactRepository;
use App\Yantrana\Components\WhatsAppService\Services\WhatsAppApiService;
use App\Yantrana\Components\Dashboard\Interfaces\DashboardEngineInterface;
use App\Yantrana\Components\Contact\Repositories\ContactCustomFieldRepository;
use App\Yantrana\Components\WhatsAppService\Repositories\WhatsAppTemplateRepository;
use App\Yantrana\Components\WhatsAppService\Repositories\WhatsAppMessageLogRepository;
use App\Yantrana\Components\WhatsAppService\Repositories\WhatsAppMessageQueueRepository;
use App\Yantrana\Components\WhatsAppService\Models\WhatsAppMessageLogModel;

class DashboardEngine extends BaseEngine implements DashboardEngineInterface
{
    /**
     * @var VendorRepository - Vendor Repository
     */
    protected $vendorRepository;
    /**
     * @var UserRepository - User Repository
     */
    protected $userRepository;

    /**
         * @var ContactRepository - Contact Repository
         */
    protected $contactRepository;

    /**
     * @var ContactGroupRepository - ContactGroup Repository
     */
    protected $contactGroupRepository;

    /**
     * @var GroupContactRepository - ContactGroup Repository
     */
    protected $groupContactRepository;

    /**
     * @var WhatsAppTemplateRepository - WhatsApp Template Repository
     */
    protected $whatsAppTemplateRepository;

    /**
     * @var WhatsAppApiService - WhatsApp API Service
     */
    protected $whatsAppApiService;

    /**
     * @var WhatsAppMessageLogRepository - Status repository
     */
    protected $whatsAppMessageLogRepository;

    /**
     * @var WhatsAppMessageQueueRepository - WhatsApp Message Queue repository
     */
    protected $whatsAppMessageQueueRepository;
    /**
     * @var CampaignRepository - Campaign repository
     */
    protected $campaignRepository;

    /**
     * @var BotReplyRepository - Bot Reply repository
     */
    protected $botReplyRepository;

    /**
     * @var  BotFlowRepository $botFlowRepository - BotFlow Repository
     */
    protected $botFlowRepository;

    /**
     * @var ContactCustomFieldRepository - ContactGroup Repository
     */
    protected $contactCustomFieldRepository;

    /**
     * @var VendorEngine - Vendor Engine
     */
    protected $vendorEngine;

    /**
     * Constructor
     *
     * @param  VendorRepository  $vendorRepository  - Vendor Repository
     * @param  UserRepository  $userRepository  - User Repository
     * @param  ContactRepository  $contactRepository  - Contact Repository
     * @param  ContactGroupRepository  $contactGroupRepository  - ContactGroup Repository
     * @param  GroupContactRepository  $groupContactRepository  - Group Contacts Repository
     * @param  WhatsAppTemplateRepository  $whatsAppTemplateRepository  - WhatsApp Templates Repository
     * @param  WhatsAppApiService  $whatsAppApiService  - WhatsApp API Service
     * @param  WhatsAppMessageQueueRepository  $whatsAppMessageQueueRepository  - WhatsApp Message Queue
     * @param  CampaignRepository  $campaignRepository  - Campaign repository
     * @param  BotReplyRepository  $botReplyRepository  - Bot Reply repository
     * @param  BotFlowRepository  $botFlowRepository  - Bot Flow repository
     * @param  ContactCustomFieldRepository  $contactCustomFieldRepository  -Custom Contact Fields repository
     * @param  VendorEngine  $vendorEngine - Vendor Engine
     *
     * @return void
     */
    public function __construct(
        VendorRepository $vendorRepository,
        UserRepository $userRepository,
        ContactRepository $contactRepository,
        ContactGroupRepository $contactGroupRepository,
        GroupContactRepository $groupContactRepository,
        WhatsAppTemplateRepository $whatsAppTemplateRepository,
        WhatsAppApiService $whatsAppApiService,
        WhatsAppMessageLogRepository $whatsAppMessageLogRepository,
        WhatsAppMessageQueueRepository $whatsAppMessageQueueRepository,
        CampaignRepository $campaignRepository,
        BotReplyRepository $botReplyRepository,
        BotFlowRepository $botFlowRepository,
        ContactCustomFieldRepository $contactCustomFieldRepository,
        VendorEngine $vendorEngine
    ) {
        $this->vendorRepository = $vendorRepository;
        $this->userRepository = $userRepository;
        $this->contactRepository = $contactRepository;
        $this->contactGroupRepository = $contactGroupRepository;
        $this->groupContactRepository = $groupContactRepository;
        $this->whatsAppTemplateRepository = $whatsAppTemplateRepository;
        $this->whatsAppApiService = $whatsAppApiService;
        $this->whatsAppMessageLogRepository = $whatsAppMessageLogRepository;
        $this->whatsAppMessageQueueRepository = $whatsAppMessageQueueRepository;
        $this->campaignRepository = $campaignRepository;
        $this->botReplyRepository = $botReplyRepository;
        $this->botFlowRepository = $botFlowRepository;
        $this->contactCustomFieldRepository = $contactCustomFieldRepository;
        $this->vendorEngine = $vendorEngine;
    }

    /**
     * Prepare Vendor Dashboard Data
     *
     * @return array
     */
    public function prepareDashboardData()
    {
        return [
            'vendorRegistrations' => $this->vendorRepository->vendorRegistrationsStats(),
            'newVendors' => $this->vendorRepository->newVendors(),
            'totalVendors' => $this->vendorRepository->countIt(),
            'totalContacts' => $this->contactRepository->countIt(),
            'totalCampaigns' => $this->campaignRepository->countIt(),
            'messagesInQueue' => $this->whatsAppMessageQueueRepository->countIt([
                'status' => 1
            ]),
            'totalMessagesProcessed' => $this->whatsAppMessageLogRepository->countIt(),
            'totalActiveVendors' => $this->vendorRepository->countIt([
                'status' => 1,
            ]),
        ];
    }

    /**
     * Prepare Vendor Dashboard Data
     *
     * @return array
     */
    public function prepareVendorDashboardData($vendorId = null, $filters = [])
    {
        if (! $vendorId) {
            $vendorId = getVendorId();
        } else {
            if (is_string($vendorId)) {
                $vendor = $this->vendorRepository->fetchIt($vendorId);
                if (! __isEmpty($vendor)) {
                    $vendorId = $vendor->_id;
                }
            }
        }
        $vendorWhereClause = [
            'vendors__id' => $vendorId
        ];
        
        $vendorModel = \App\Yantrana\Components\Vendor\Models\VendorModel::find($vendorId);
        $planCredits = $vendorModel->plan_ai_credits ?? 0;
        $extraCredits = $vendorModel->extra_ai_credits ?? 0;
        $totalCredits = $planCredits + $extraCredits;
        $displayCredits = $planCredits >= 99999999 ? __tr('Unlimited') : (string)$totalCredits;

        $messageHistory = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $incoming = WhatsAppMessageLogModel::where('vendors__id', $vendorId)
                ->where('is_incoming_message', 1)
                ->whereDate('created_at', $date)
                ->count();
            $outgoing = WhatsAppMessageLogModel::where('vendors__id', $vendorId)
                ->where('is_incoming_message', '!=', 1)
                ->whereDate('created_at', $date)
                ->count();
            $messageHistory[] = [
                'label' => $date->format('d/m'),
                'incoming' => $incoming,
                'outgoing' => $outgoing,
            ];
        }

        $userId = getUserID();
        $isRestrictedVendorUser = !hasVendorAccess()
            ? hasVendorAccess('assigned_chats_only')
            : false;

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $dayBeforeYesterday = Carbon::today()->subDays(2);

        $statsQuery = \DB::table('labels')
            ->leftJoin('contact_labels', 'labels._id', '=', 'contact_labels.labels__id')
            ->leftJoin('contacts', function($join) use ($isRestrictedVendorUser, $userId, $filters) {
                $join->on('contact_labels.contacts__id', '=', 'contacts._id');
                if ($isRestrictedVendorUser) {
                    $join->where('contacts.assigned_users__id', '=', $userId);
                } elseif (!empty($filters['agent_id'])) {
                    if ($filters['agent_id'] === 'unassigned') {
                        $join->whereNull('contacts.assigned_users__id');
                    } else {
                        $join->where('contacts.assigned_users__id', '=', $filters['agent_id']);
                    }
                }
            })
            ->where('labels.vendors__id', $vendorId)
            ->where('labels.status', 1);

        // If custom date filter is active
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $startDate = Carbon::parse($filters['start_date'])->startOfDay();
            $endDate = Carbon::parse($filters['end_date'])->endOfDay();
            $statsQuery->whereBetween('contact_labels.created_at', [$startDate, $endDate]);
        }

        $statsQuery->select(
            'labels._id as label_id',
            'labels._uid as label_uid',
            'labels.title as label_title',
            'labels.text_color',
            'labels.bg_color',
            \DB::raw("SUM(CASE WHEN contact_labels.created_at IS NOT NULL AND DATE(contact_labels.created_at) = '{$today->toDateString()}' THEN 1 ELSE 0 END) as count_today"),
            \DB::raw("SUM(CASE WHEN contact_labels.created_at IS NOT NULL AND DATE(contact_labels.created_at) = '{$yesterday->toDateString()}' THEN 1 ELSE 0 END) as count_yesterday"),
            \DB::raw("SUM(CASE WHEN contact_labels.created_at IS NOT NULL AND DATE(contact_labels.created_at) = '{$dayBeforeYesterday->toDateString()}' THEN 1 ELSE 0 END) as count_day_before"),
            \DB::raw("COUNT(contacts._id) as count_total")
        )->groupBy('labels._id', 'labels._uid', 'labels.title', 'labels.text_color', 'labels.bg_color');

        $labelStats = $statsQuery->get();

        $agents = [];
        if (!$isRestrictedVendorUser) {
            $agents = \DB::table('users')
                ->where('vendors__id', $vendorId)
                ->where('status', 1)
                ->select('_id', '_uid', 'first_name', 'last_name', 'email')
                ->get();
        }

        return array_merge([
            'firstOfMonth' => Carbon::now()->firstOfMonth(),
            'lastOfMonth' => Carbon::now()->lastOfMonth(),
            'vendorId' => $vendorId,
            'activeTeamMembers' => $this->userRepository->countVendorsActiveUsers($vendorWhereClause),
            'vendorUserData' => auth()->user(),
            'totalContacts' => $this->contactRepository->totalContactsCountForVendor($vendorId),
            'totalGroups' => $this->contactGroupRepository->countIt($vendorWhereClause),
            'totalCampaigns' => $this->campaignRepository->countIt($vendorWhereClause),
            'totalTemplates' => $this->whatsAppTemplateRepository->countIt($vendorWhereClause),
            'totalBotReplies' => $this->botReplyRepository->fetchBotReplyCountForDashboard($vendorId),
            'messagesInQueue' => $this->whatsAppMessageQueueRepository->countIt([
                'status' => 1,
                'vendors__id' => $vendorId
            ]),
            'totalMessagesProcessed' => $this->whatsAppMessageLogRepository->countIt(
                array_merge($vendorWhereClause, ['is_system_message' => null])
            ),
            'activeContacts24h' => \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)
                ->whereHas('lastIncomingMessage', function($query) {
                    $query->where('messaged_at', '>', now()->subHours(24));
                })
                ->select('_id', '_uid', 'first_name', 'last_name', 'wa_id')
                ->get(),
            'activeContacts24hCount' => \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)
                ->whereHas('lastIncomingMessage', function($query) {
                    $query->where('messaged_at', '>', now()->subHours(24));
                })
                ->count(),
            'unreadMessagesCount' => $this->whatsAppMessageLogRepository->getUnreadCount($vendorId),
            'messagesReceivedTodayCount' => WhatsAppMessageLogModel::where('vendors__id', $vendorId)
                ->where('is_incoming_message', 1)
                ->whereDate('created_at', Carbon::today())
                ->count(),
            'ordersCount' => \Schema::hasTable('orders') ? \DB::table('orders')->where('vendors__id', $vendorId)->count() : 0,
            'vendorInfo' => $this->vendorEngine->getBasicSettings($vendorId),
            'messageHistory' => $messageHistory,
            'label_date_stats' => $labelStats,
            'agents' => $agents,
            'ai_credits' => [
                'is_enabled' => (bool) vendorPlanDetails('ai_chat_bot', 1, $vendorId)['is_limit_available'],
                'bot_active' => (bool) getVendorSettings('enable_open_ai_bot', null, null, $vendorId),
                'plan_credits' => $planCredits,
                'extra_credits' => $extraCredits,
                'total_credits' => $totalCredits,
                'display_credits' => $displayCredits,
            ],
            'vendorUserData' => getUserAuthInfo('profile'),
        ]);
    }

    /**
     * Check plan uses against the plan
     *
     * @param array $planDetails
     * @param int $vendorId
     * @return string
     */
    function checkPlanUsages($planDetails, $vendorId) {
        $vendorWhereClause = [
            'vendors__id' => $vendorId
        ];
        $featuresLimitUnavailable = [];
        $onOffFeatures = [
            'ai_chat_bot' => isAiBotAvailable($vendorId),
            'api_access' => getVendorSettings('enable_vendor_webhook', null, null, $vendorId)
        ];
        $subscription = getVendorCurrentActiveSubscription($vendorId);
        $currentBillingCycle = app()->make(\App\Yantrana\Components\WhatsAppService\WhatsAppServiceEngine::class)->getCurrentBillingCycleDates($subscription->created_at ?? getUserAuthInfo('vendor_created_at'));
        $usagesCountCollection = [
            'contacts' => $this->contactRepository->countIt($vendorWhereClause),
            'campaigns' => $this->campaignRepository->countIt([
                'vendors__id' => $vendorId,
                [
                    'created_at', '>=', $currentBillingCycle['start'],
                ], [
                    'created_at', '<=', $currentBillingCycle['end'],
                ]
            ]),
            'bot_replies' => $this->botReplyRepository->fetchBotReplyCount($vendorId),
            'bot_flows' => $this->botFlowRepository->countIt($vendorWhereClause),
            'contact_custom_fields' => $this->contactCustomFieldRepository->countIt($vendorWhereClause),
            'system_users' => $this->userRepository->countIt($vendorWhereClause),
            'drip_campaigns' => class_exists('\Addons\WhatsJetDripCampaignAddon\Models\DripCampaign') ? \Addons\WhatsJetDripCampaignAddon\Models\DripCampaign::where('vendors__id', $vendorId)->count() : 0,
        ];
        foreach ($planDetails['features'] as $planFeatureKey => $planFeature) {
            if(isset($usagesCountCollection[$planFeatureKey])) {
                $vendorPlanDetails = vendorPlanDetails($planFeatureKey, $usagesCountCollection[$planFeatureKey], $vendorId, [
                    'plan_id' => $planDetails['id'],
                    'expiry_check' => false
                ]);
                if(!$vendorPlanDetails->isLimitAvailable()) {
                    $featuresLimitUnavailable[] = $planFeature['description'];
                }
            }
            if(isset($onOffFeatures[$planFeatureKey])) {
                $vendorPlanDetails = vendorPlanDetails($planFeatureKey, 0, $vendorId, [
                    'plan_id' => $planDetails['id'],
                     'expiry_check' => false
                ]);
                if($onOffFeatures[$planFeatureKey] and !$vendorPlanDetails->isLimitAvailable()) {
                    $featuresLimitUnavailable[] = $planFeature['description'];
                }
            }
        }
        return trim(implode(', ', $featuresLimitUnavailable ?? []));
    }

    /**
    * Prepare vendor quick view data
    *
    * @param  mix  $vendorIdOrUid
    * @return  EngineResponse
    *---------------------------------------------------------------- */
    public function prepareVendorQuickViewData($vendorIdOrUid)
    {
        $vendor = $this->vendorRepository->fetchIt($vendorIdOrUid);
        // Check if $vendor not exist then throw not found
        // exception
        if (__isEmpty($vendor)) {
            return $this->engineReaction(18, null, __tr('Vendor not found.'));
        }

        $vendorDashboardData = $this->prepareVendorDashboardData($vendor->_id);

        $whatsappSetupStatusMessage = '';
        $whatsappSetupStatus = false;

        if (getVendorSettings('whatsapp_access_token_expired', null, null, $vendorIdOrUid)) {
            $whatsappSetupStatusMessage = 'WhatsApp token seems to be expired';
        } elseif (!isWhatsAppBusinessAccountReady($vendorIdOrUid)) {
            $whatsappSetupStatusMessage = 'WhatsApp Setup is Incomplete';
        } else {
            $whatsappSetupStatusMessage = 'WhatsApp Setup is Completed';
            $whatsappSetupStatus = true;
        }

        $vendorDashboardData['whatsappSetupStatusMessage'] = $whatsappSetupStatusMessage;
        $vendorDashboardData['whatsappSetupStatus'] = $whatsappSetupStatus;
        $vendorDashboardData['whatsapp_phone_number'] = getVendorSettings('whatsapp_phone_number_from_whatsapp', null, null, $vendorIdOrUid);
        if (empty($vendorDashboardData['whatsapp_phone_number'])) {
            $vendorDashboardData['whatsapp_phone_number'] = getVendorSettings('whatsapp_phone_number', null, null, $vendorIdOrUid);
        }

        
        updateClientModels([
            'quickViewData' => $vendorDashboardData
        ]);

        return $this->engineSuccessResponse([
            'vendorDashboardData' => $vendorDashboardData
        ]);
    }
}
