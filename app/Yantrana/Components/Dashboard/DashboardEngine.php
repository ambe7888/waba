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

        $sevenDaysAgo = Carbon::today()->subDays(6)->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();

        $historyRows = WhatsAppMessageLogModel::where('vendors__id', $vendorId)
            ->whereBetween('created_at', [$sevenDaysAgo, $todayEnd])
            ->selectRaw("
                DATE(created_at) as log_date,
                SUM(CASE WHEN is_incoming_message = 1 THEN 1 ELSE 0 END) as incoming,
                SUM(CASE WHEN is_incoming_message != 1 THEN 1 ELSE 0 END) as outgoing
            ")
            ->groupBy('log_date')
            ->get()
            ->keyBy('log_date');

        $messageHistory = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateKey = $date->toDateString();
            $row = $historyRows->get($dateKey);
            $messageHistory[] = [
                'label' => $date->format('d/m'),
                'incoming' => (int) ($row->incoming ?? 0),
                'outgoing' => (int) ($row->outgoing ?? 0),
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
            \DB::raw("SUM(CASE WHEN contacts._id IS NOT NULL AND contact_labels.created_at IS NOT NULL AND DATE(contact_labels.created_at) = '{$today->toDateString()}' THEN 1 ELSE 0 END) as count_today"),
            \DB::raw("SUM(CASE WHEN contacts._id IS NOT NULL AND contact_labels.created_at IS NOT NULL AND DATE(contact_labels.created_at) = '{$yesterday->toDateString()}' THEN 1 ELSE 0 END) as count_yesterday"),
            \DB::raw("SUM(CASE WHEN contacts._id IS NOT NULL AND contact_labels.created_at IS NOT NULL AND DATE(contact_labels.created_at) = '{$dayBeforeYesterday->toDateString()}' THEN 1 ELSE 0 END) as count_day_before"),
            \DB::raw("COUNT(contacts._id) as count_total")
        )->groupBy('labels._id', 'labels._uid', 'labels.title', 'labels.text_color', 'labels.bg_color');

        $labelStats = $statsQuery->get();

        $agents = [];
        if (!$isRestrictedVendorUser) {
            $agents = $this->userRepository->fetchAgentsList($vendorId)
                ->where('status', 1)
                ->where('user_roles__id', 3) // agents/team members only, not the vendor admin (2)
                ->values();
        }

        $vendorUserData = auth()->user();
        
        $vendorUserPermissions = [];
        if (!isVendorAdmin(getVendorId())) {
            $vendorUserPermissions = getUserAuthInfo('permissions') ?: [];
        }

        $campaignBillingCycle = app()->make(\App\Yantrana\Components\WhatsAppService\WhatsAppServiceEngine::class)
            ->getCurrentBillingCycleDates(getVendorCurrentActiveSubscription($vendorId)->created_at ?? $vendorModel->created_at);
        $campaignsThisBillingCycle = $this->campaignRepository->countIt([
            'vendors__id' => $vendorId,
            ['created_at', '>=', $campaignBillingCycle['start']],
            ['created_at', '<=', $campaignBillingCycle['end']],
        ]);

        // 1 single query for campaign message totals
        $campaignMsgStats = WhatsAppMessageLogModel::where('vendors__id', $vendorId)
            ->where('is_incoming_message', 0)
            ->whereNotNull('campaigns__id')
            ->selectRaw("
                COUNT(*) as total_sent,
                SUM(CASE WHEN status IN ('delivered', 'read') THEN 1 ELSE 0 END) as total_delivered,
                SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as total_read
            ")
            ->first();
        $totalMessagesSent = (int) ($campaignMsgStats->total_sent ?? 0);
        $totalDeliveredMessages = (int) ($campaignMsgStats->total_delivered ?? 0);
        $totalMessagesRead = (int) ($campaignMsgStats->total_read ?? 0);

        // Fast active contacts in 24h
        $since24h = Carbon::now()->subHours(24);
        if (\Schema::hasColumn('contacts', 'last_incoming_message_at')) {
            $activeContactsQuery = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)
                ->where('last_incoming_message_at', '>', $since24h);
        } else {
            $activeContactsQuery = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)
                ->where('messaged_at', '>', $since24h);
        }
        $activeContacts24hCount = (clone $activeContactsQuery)->count();
        $activeContacts24h = $activeContactsQuery->select('_id', '_uid', 'first_name', 'last_name', 'wa_id')
            ->orderBy('updated_at', 'desc')
            ->limit(100)
            ->get();

        // 1 single query for today & yesterday incoming/processed message totals
        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();
        $yesterdayStart = Carbon::yesterday()->startOfDay();
        $yesterdayEnd = Carbon::yesterday()->endOfDay();

        $dailyStats = WhatsAppMessageLogModel::where('vendors__id', $vendorId)
            ->whereBetween('created_at', [$yesterdayStart, $todayEnd])
            ->selectRaw("
                SUM(CASE WHEN is_incoming_message = 1 AND created_at >= '{$todayStart}' THEN 1 ELSE 0 END) as received_today,
                SUM(CASE WHEN is_incoming_message = 1 AND created_at BETWEEN '{$yesterdayStart}' AND '{$yesterdayEnd}' THEN 1 ELSE 0 END) as received_yesterday,
                SUM(CASE WHEN is_system_message IS NULL AND created_at >= '{$todayStart}' THEN 1 ELSE 0 END) as processed_today,
                SUM(CASE WHEN is_system_message IS NULL AND created_at BETWEEN '{$yesterdayStart}' AND '{$yesterdayEnd}' THEN 1 ELSE 0 END) as processed_yesterday
            ")
            ->first();

        $messagesReceivedTodayCount = (int) ($dailyStats->received_today ?? 0);
        $messagesReceivedYesterdayCount = (int) ($dailyStats->received_yesterday ?? 0);
        $messagesProcessedTodayCount = (int) ($dailyStats->processed_today ?? 0);
        $messagesProcessedYesterdayCount = (int) ($dailyStats->processed_yesterday ?? 0);

        $messagesReceivedDiffPercent = $messagesReceivedYesterdayCount > 0 
            ? (int) round((($messagesReceivedTodayCount - $messagesReceivedYesterdayCount) / $messagesReceivedYesterdayCount) * 100)
            : ($messagesReceivedTodayCount > 0 ? 100 : 0);

        $messagesProcessedDiffPercent = $messagesProcessedYesterdayCount > 0
            ? (int) round((($messagesProcessedTodayCount - $messagesProcessedYesterdayCount) / $messagesProcessedYesterdayCount) * 100)
            : ($messagesProcessedTodayCount > 0 ? 100 : 0);

        $uniqueContactsTodayCount = WhatsAppMessageLogModel::where('vendors__id', $vendorId)
            ->where('is_incoming_message', 1)
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->distinct('contacts__id')
            ->count('contacts__id');

        $ordersCount = 0;
        $ordersTodayCount = 0;
        $ordersYesterdayCount = 0;
        $ordersDiffPercent = 0;
        $orderStats = [];
        if (\Schema::hasTable('orders')) {
            $ordersCount = \DB::table('orders')->where('vendors__id', $vendorId)->count();
            $orderDailyStats = \DB::table('orders')->where('vendors__id', $vendorId)
                ->whereBetween('created_at', [$yesterdayStart, $todayEnd])
                ->selectRaw("
                    SUM(CASE WHEN created_at >= '{$todayStart}' THEN 1 ELSE 0 END) as orders_today,
                    SUM(CASE WHEN created_at BETWEEN '{$yesterdayStart}' AND '{$yesterdayEnd}' THEN 1 ELSE 0 END) as orders_yesterday,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                ")
                ->first();
            $ordersTodayCount = (int) ($orderDailyStats->orders_today ?? 0);
            $ordersYesterdayCount = (int) ($orderDailyStats->orders_yesterday ?? 0);
            $ordersDiffPercent = $ordersYesterdayCount > 0
                ? (int) round((($ordersTodayCount - $ordersYesterdayCount) / $ordersYesterdayCount) * 100)
                : ($ordersTodayCount > 0 ? 100 : 0);
            $orderStats = [
                'pending' => (int) ($orderDailyStats->pending ?? 0),
                'completed' => (int) ($orderDailyStats->completed ?? 0),
            ];
        }

        $templateStatsRaw = \App\Yantrana\Components\WhatsAppService\Models\WhatsAppTemplateModel::where('vendors__id', $vendorId)
            ->selectRaw("
                SUM(CASE WHEN status = 'APPROVED' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'REJECTED' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN category = 'MARKETING' THEN 1 ELSE 0 END) as marketing,
                SUM(CASE WHEN category = 'UTILITY' THEN 1 ELSE 0 END) as utility
            ")
            ->first();
        $templateStats = [
            'approved' => (int) ($templateStatsRaw->approved ?? 0),
            'pending' => (int) ($templateStatsRaw->pending ?? 0),
            'rejected' => (int) ($templateStatsRaw->rejected ?? 0),
            'marketing' => (int) ($templateStatsRaw->marketing ?? 0),
            'utility' => (int) ($templateStatsRaw->utility ?? 0),
        ];

        return array_merge([
            'campaignsThisBillingCycle' => $campaignsThisBillingCycle,
            'campaignBillingCycleEnd' => $campaignBillingCycle['end'],
            'firstOfMonth' => Carbon::now()->firstOfMonth(),
            'lastOfMonth' => Carbon::now()->lastOfMonth(),
            'vendorId' => $vendorId,
            'activeTeamMembers' => $this->userRepository->countVendorsActiveUsers($vendorWhereClause),
            'vendorUserData' => clone $vendorUserData,
            'vendorUserPermissions' => $vendorUserPermissions,
            'totalContacts' => $this->contactRepository->totalContactsCountForVendor($vendorId),
            'totalGroups' => $this->contactGroupRepository->countIt($vendorWhereClause),
            'totalCampaigns' => $this->campaignRepository->countIt($vendorWhereClause),
            'totalMessagesSent' => $totalMessagesSent,
            'totalDeliveredMessages' => $totalDeliveredMessages,
            'totalMessagesRead' => $totalMessagesRead,
            'totalTemplates' => $this->whatsAppTemplateRepository->countIt($vendorWhereClause),
            'totalBotReplies' => $this->botReplyRepository->fetchBotReplyCountForDashboard($vendorId),
            'totalBotFlows' => $this->botFlowRepository->countIt($vendorWhereClause),
            'totalDripCampaigns' => class_exists('\Addons\WhatsJetDripCampaignAddon\Models\DripCampaign') ? \Addons\WhatsJetDripCampaignAddon\Models\DripCampaign::where('vendors__id', $vendorId)->count() : 0,
            'messagesInQueue' => $this->whatsAppMessageQueueRepository->countIt([
                'status' => 1,
                'vendors__id' => $vendorId
            ]),
            'totalMessagesProcessed' => $this->whatsAppMessageLogRepository->countIt(
                array_merge($vendorWhereClause, ['is_system_message' => null])
            ),
            'activeContacts24h' => $activeContacts24h,
            'activeContacts24hCount' => $activeContacts24hCount,
            'unreadMessagesCount' => $this->whatsAppMessageLogRepository->getUnreadCount($vendorId),
            'unreadContactsCount' => WhatsAppMessageLogModel::where('vendors__id', $vendorId)
                ->where('is_incoming_message', 1)
                ->where('status', 'received')
                ->distinct('contacts__id')
                ->count('contacts__id'),
            'messagesReceivedTodayCount' => $messagesReceivedTodayCount,
            'uniqueContactsTodayCount' => $uniqueContactsTodayCount,
            'messagesReceivedYesterdayCount' => $messagesReceivedYesterdayCount,
            'messagesReceivedDiffPercent' => $messagesReceivedDiffPercent,
            'ordersCount' => $ordersCount,
            'ordersTodayCount' => $ordersTodayCount,
            'ordersYesterdayCount' => $ordersYesterdayCount,
            'ordersDiffPercent' => $ordersDiffPercent,
            'messagesProcessedTodayCount' => $messagesProcessedTodayCount,
            'messagesProcessedYesterdayCount' => $messagesProcessedYesterdayCount,
            'messagesProcessedDiffPercent' => $messagesProcessedDiffPercent,
            'vendorInfo' => $this->vendorEngine->getBasicSettings($vendorId),
            'whatsapp_setup' => [
                'is_connected' => isWhatsAppBusinessAccountReady($vendorId),
                'phone_number' => getVendorSettings('current_phone_number_number', null, null, $vendorId),
                'phone_number_id' => getVendorSettings('current_phone_number_id', null, null, $vendorId),
                'waba_id' => getVendorSettings('whatsapp_business_account_id', null, null, $vendorId),
                'app_id' => getVendorSettings('facebook_app_id', null, null, $vendorId),
            ],
            'messageHistory' => $messageHistory,
            'campaign_stats' => (function() use ($vendorId) {
                $vendorCampaigns = \App\Yantrana\Components\Campaign\Models\CampaignModel::where('vendors__id', $vendorId)->withCount(['queuePendingMessages', 'queueProcessingMessages', 'messageLog'])->get();
                $cCompleted = 0; $cProcessing = 0; $cScheduled = 0; $cArchived = 0;
                foreach($vendorCampaigns as $c) {
                    if ($c->status == 6) { $cArchived++; continue; }
                    if ($c->queue_pending_messages_count || $c->queue_processing_messages_count) {
                        if ($c->message_log_count) { $cProcessing++; } else { $cScheduled++; }
                    } else {
                        if ($c->message_log_count) { $cCompleted++; }
                    }
                }
                return [
                    'completed' => $cCompleted,
                    'processing' => $cProcessing,
                    'scheduled' => $cScheduled,
                    'archived' => $cArchived,
                ];
            })(),
            'template_stats' => $templateStats,
            'order_stats' => $orderStats,
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
            'current_subscription' => (function() use ($vendorId) {
                // Same helper the web "subscription expiring/expired" banner
                // (layouts/app.blade.php) reads from - reused here so the app
                // gets the identical status/message/expiry signals, including
                // for a vendor currently on their post-signup trial.
                $vendorPlanDetails = vendorPlanDetails(null, null, $vendorId);
                $sub = getVendorCurrentActiveSubscription($vendorId);
                $title = 'Plan Gratuit';
                $endsAt = null;
                $isExpired = false;
                $isFree = true;
                $isTrial = false;
                $remainingDays = 0;
                $totalDays = 30;
                $progress = 1.0;
                $price = 0;
                $billingCycle = 'Mensuel';

                if (empty($sub) and $vendorPlanDetails->planType() === 'trial') {
                    $title = $vendorPlanDetails['plan_title'] . ' (' . __tr('Essai') . ')';
                    $isFree = false;
                    // Trial and paid subscription were indistinguishable to
                    // the app, so a vendor on their post-signup free week was
                    // told their "abonnement" was expiring - when they have
                    // never had one.
                    $isTrial = true;
                    $trialEndsAt = $vendorPlanDetails['ends_at'];
                    if ($trialEndsAt) {
                        $parsedEndsAt = \Carbon\Carbon::parse($trialEndsAt);
                        $endsAt = $parsedEndsAt;
                        $isExpired = $parsedEndsAt->isPast();
                        $remainingDays = max(0, \Carbon\Carbon::now()->diffInDays($parsedEndsAt, false));
                        $totalDays = 7;
                        $progress = min(1.0, max(0.0, $remainingDays / $totalDays));
                    }
                } elseif (!empty($sub)) {
                    $planId = $sub->plan_id ?? $sub->type ?? null;
                    $title = getPaidPlans("{$planId}.title");
                    if (empty($title)) {
                        $title = $sub->plan_title ?? $sub->title ?? 'Plan Vendeur';
                    }
                    $endsAt = $sub->expiry_at ?? $sub->ends_at ?? null;
                    $isFree = false;

                    if ($endsAt) {
                        try {
                            $parsedEndsAt = \Carbon\Carbon::parse($endsAt);
                            $isExpired = $parsedEndsAt->isPast();
                            $remainingDays = max(0, \Carbon\Carbon::now()->diffInDays($parsedEndsAt, false));
                            if (isset($sub->created_at)) {
                                $totalDays = max(1, \Carbon\Carbon::parse($sub->created_at)->diffInDays($parsedEndsAt));
                            }
                            $progress = min(1.0, max(0.0, $remainingDays / max(1, $totalDays)));
                        } catch (\Exception $e) {}
                    }
                    
                    $planCharges = getPaidPlans("{$planId}.charges");
                    if (!empty($planCharges)) {
                        $firstCharge = current($planCharges);
                        $price = $firstCharge['charge'] ?? 0;
                        reset($planCharges);
                        $billingCycle = key($planCharges) == 'monthly' ? 'Mensuel' : 'Annuel';
                    }
                } else {
                    $freePlan = getFreePlan();
                    if (!empty($freePlan) && isset($freePlan['title'])) {
                        $title = $freePlan['title'];
                    }
                }

                return [
                    'title' => $title,
                    'ends_at' => $endsAt ? (is_string($endsAt) ? $endsAt : $endsAt->toIso8601String()) : null,
                    'is_expired' => $isExpired,
                    'is_free' => $isFree,
                    'is_trial' => $isTrial,
                    'remaining_days' => $remainingDays,
                    'total_days' => $totalDays,
                    'progress' => $progress,
                    'price' => $price,
                    'billing_cycle' => $billingCycle,
                    'features' => [
                        'manage_orders' => (bool) (vendorPlanDetails('ecommerce_catalog', 1, $vendorId)['is_limit_available'] ?? true),
                        'ai_bot' => (bool) (vendorPlanDetails('ai_chat_bot', 1, $vendorId)['is_limit_available'] ?? true),
                        'campaigns' => (bool) (vendorPlanDetails('campaigns', 1, $vendorId)['is_limit_available'] ?? true),
                        'canned_replies' => (bool) (vendorPlanDetails('bot_replies', 1, $vendorId)['is_limit_available'] ?? true),
                    ],
                    'limits' => [
                        'contacts' => vendorPlanDetails('contacts', 0, $vendorId)->plan_feature_limit ?? 0,
                        'campaigns' => vendorPlanDetails('campaigns', 0, $vendorId)->plan_feature_limit ?? 0,
                        'bot_replies' => vendorPlanDetails('bot_replies', 0, $vendorId)->plan_feature_limit ?? 0,
                        'system_users' => vendorPlanDetails('system_users', 0, $vendorId)->plan_feature_limit ?? 0,
                        'drip_campaigns' => vendorPlanDetails('drip_campaigns', 0, $vendorId)->plan_feature_limit ?? 0,
                        'bot_flows' => vendorPlanDetails('bot_flows', 0, $vendorId)->plan_feature_limit ?? 0,
                    ],
                    // Mirrors the alert banner in layouts/app.blade.php (web) so the
                    // app can show the same red/orange plan-status alert.
                    'alert' => (function () use ($vendorPlanDetails, $isTrial) {
                        if (!$vendorPlanDetails->hasActivePlan()) {
                            return ['level' => 'danger', 'message' => $vendorPlanDetails->message()];
                        }
                        if ($vendorPlanDetails['is_expiring']) {
                            // Someone on the post-signup free week has no
                            // subscription yet, so telling them theirs is
                            // expiring is simply wrong.
                            return ['level' => 'warning', 'message' => $isTrial
                                ? __tr("Votre période d'essai se termine le __endAt__", [
                                    '__endAt__' => formatDate($vendorPlanDetails['ends_at']),
                                ])
                                : __tr('Your subscription plan is expiring on __endAt__', [
                                    '__endAt__' => formatDate($vendorPlanDetails['ends_at']),
                                ])];
                        }
                        return null;
                    })(),
                ];
            })(),
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
