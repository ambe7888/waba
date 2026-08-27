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
 * DashboardController.php - Controller file
 *
 * This file is part of the Dashboard component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Dashboard\Controllers;

use App\Yantrana\Base\BaseController;
use App\Yantrana\Components\Dashboard\DashboardEngine;
use App\Yantrana\Support\CommonRequest;

class DashboardController extends BaseController
{
    /**
     * @var DashboardEngine - Dashboard Engine
     */
    protected $dashboardEngine;

    /**
     * Constructor
     *
     * @param  DashboardEngine  $dashboardEngine  - Dashboard Engine
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(DashboardEngine $dashboardEngine)
    {
        $this->dashboardEngine = $dashboardEngine;
    }

    /**
     * Dashboard View
     */
    public function dashboardView()
    {

        return $this->loadView(
            'dashboard',
            $this->dashboardEngine->prepareDashboardData()
        );
    }

    /**
     * Dashboard View
     */
    public function vendorDashboardView()
    {
        $vendorId = getVendorId();
        $userId = getUserID();
        $cacheKey = "vendor_dashboard_view_{$vendorId}_{$userId}";

        $data = \Cache::remember($cacheKey, 300, function() {
            return $this->dashboardEngine->prepareVendorDashboardData();
        });

        // Always override cached user data with current user
        $data['vendorUserData'] = auth()->user();
        if (!isVendorAdmin($vendorId)) {
            $data['vendorUserPermissions'] = getUserAuthInfo('permissions') ?: [];
        } else {
            $data['vendorUserPermissions'] = [];
        }

        return $this->loadView(
            'vendors.vendor-dashboard',
            $data
        );
    }

    /**
     * Dashboard Data API for Mobile App
     *
     * Strategy:
     *  - On first call (cold cache): immediately return a lightweight "loading"
     *    response, trigger the heavy computation in a background process so the
     *    cache is warm for the next poll.
     *  - On subsequent calls (warm cache): serve from cache in < 50 ms.
     *  - display_values: pre-formatted numbers sent as plain text strings so the
     *    Flutter app displays the exact value (e.g. "15 234") without the
     *    built-in K/M compactor kicking in — configurable server-side, no APK
     *    recompile required.
     *
     * @return json object
     */
    public function apiVendorDashboardStats(CommonRequest $request)
    {
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'agent_id' => $request->input('agent_id'),
        ];

        $vendorId = getVendorId();
        $userId   = getUserID();
        $cacheKey = "api_vendor_dashboard_{$vendorId}_{$userId}_" . md5(json_encode($filters));

        // ── Cold cache: return a minimal skeleton and warm the cache async ──
        if (!\Cache::has($cacheKey)) {
            // Dispatch warm-up in background so next request is instant
            $engine = $this->dashboardEngine;
            $capturedFilters = $filters;
            dispatch(function () use ($engine, $capturedFilters, $cacheKey) {
                $data = $engine->prepareVendorDashboardData(null, $capturedFilters);
                \Cache::put($cacheKey, $data, 300);
            })->afterResponse();

            return $this->processResponse(1, [], [
                '_cache_warming' => true,
                'vendorUserData' => auth()->user(),
            ]);
        }

        // ── Warm cache: serve immediately ──
        $data = \Cache::get($cacheKey);

        // Always override cached user data with current user (never cache-shared)
        $data['vendorUserData']        = auth()->user();
        $data['vendorUserPermissions'] = isVendorAdmin($vendorId)
            ? []
            : (getUserAuthInfo('permissions') ?: []);

        // ── display_values: server-formatted numbers for the Flutter UI ──
        // If a key is a plain integer, format it with thousands separator so the
        // app renders "15 234" instead of "15,2K". The app already treats any
        // non-numeric string as a verbatim display value (see number_format_utils).
        $numericKeys = [
            'totalContacts', 'totalGroups', 'totalCampaigns',
            'totalMessagesSent', 'totalDeliveredMessages', 'totalMessagesRead',
            'totalTemplates', 'totalBotReplies', 'totalBotFlows',
            'totalDripCampaigns', 'messagesInQueue', 'totalMessagesProcessed',
            'activeContacts24hCount', 'unreadMessagesCount', 'unreadContactsCount',
            'messagesReceivedTodayCount', 'uniqueContactsTodayCount',
            'messagesReceivedYesterdayCount', 'messagesProcessedTodayCount',
            'messagesProcessedYesterdayCount', 'ordersCount', 'ordersTodayCount',
            'ordersYesterdayCount', 'activeTeamMembers',
        ];
        $displayValues = [];
        foreach ($numericKeys as $key) {
            if (isset($data[$key]) && is_numeric($data[$key])) {
                $displayValues[$key] = number_format((int) $data[$key], 0, ',', ' ');
            }
        }
        $data['display_values'] = $displayValues;

        return $this->processResponse(1, [], $data);
    }

    /**
     * Toggle OpenAI Bot replies status
     *
     * @return json object
     */
    public function toggleBotReply()
    {
        $vendorId = getVendorId();
        if (!$vendorId) {
            return $this->processResponse(2, [], [
                'message' => __tr('Non autorisé. Compte vendeur requis.')
            ]);
        }
        
        $currentState = getVendorSettings('enable_open_ai_bot', null, null, $vendorId);
        $newState = $currentState ? 0 : 1;

        $settingsRepository = new \App\Yantrana\Components\Vendor\Repositories\VendorSettingsRepository();
        $success = $settingsRepository->storeOrUpdate([
            'enable_open_ai_bot' => [
                'value' => $newState,
                'data_type' => 3,
                'name' => 'enable_open_ai_bot',
            ]
        ], $vendorId);

        if ($success) {
            return $this->processResponse(1, [], [
                'enable_open_ai_bot' => $newState,
                'message' => $newState ? __tr('Bot replies enabled.') : __tr('Bot replies disabled.')
            ]);
        }

        return $this->processResponse(2, [], [
            'message' => __tr('Failed to toggle bot replies.')
        ]);
    }

    /**
     * Dashboard Stats Data Filter
     *
     *
     * @return json object
     */
    public function dashboardStatsDataFilter(CommonRequest $request, $vendorUid = null)
    {
        $request->validate([
            'daterange' => [
                'required',
            ],
        ]);
        // Update client side Alpine Bindings
        updateClientModels(array_merge(['isDurationFilterActivated' => false]));

        return $this->processResponse(1, [], [], true);
    }

    /**
     * Get vendor quick view data
     *
     * @param  mix  $vendorIdOrUid
     * @return array
     */
    public function getVendorQuickViewData($vendorIdOrUid)
    {
        // ask engine to process the request
        $processReaction = $this->dashboardEngine->prepareVendorQuickViewData($vendorIdOrUid);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }
}
