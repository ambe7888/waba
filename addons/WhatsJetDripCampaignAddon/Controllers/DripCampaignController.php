<?php

namespace Addons\WhatsJetDripCampaignAddon\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Addons\WhatsJetDripCampaignAddon\Models\DripCampaign;
use Addons\WhatsJetDripCampaignAddon\Models\DripStep;
use Addons\WhatsJetDripCampaignAddon\Models\DripSubscriber;
use App\Yantrana\Components\WhatsAppService\Models\WhatsAppTemplateModel;

class DripCampaignController extends Controller
{
    /**
     * List all Drip Campaigns for the current vendor
     */
    public function index()
    {
        $vendorId = getVendorId();
        
        $campaigns = DripCampaign::where('vendors__id', $vendorId)
            ->withCount('steps')
            ->withCount('subscribers')
            ->withCount(['subscribers as active_subscribers_count' => function ($query) {
                $query->where('status', 1);
            }])
            ->orderBy('_id', 'desc')
            ->get();
            
        return view('WhatsJetDripCampaignAddon::list', [
            'campaigns' => $campaigns
        ]);
    }

    /**
     * Create a new Drip Campaign
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $vendorId = getVendorId();

        // Check plan limits
        $vendorPlanDetails = vendorPlanDetails('drip_campaigns', DripCampaign::where('vendors__id', $vendorId)->count(), $vendorId);
        if (!$vendorPlanDetails['is_limit_available']) {
            return redirect()->back()->with('error', $vendorPlanDetails['message']);
        }

        $campaign = DripCampaign::create([
            'vendors__id' => $vendorId,
            'title' => $request->title,
            'status' => 1
        ]);

        return redirect()->route('addon.WhatsJetDripCampaignAddon.builder', ['campaignId' => $campaign->_uid])
            ->with('success', __tr('Drip Campaign created successfully. Now add some steps!'));
    }

    /**
     * Create a new Drip Campaign — clean JSON response for the mobile app
     */
    public function apiStore(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $vendorId = getVendorId();

        $vendorPlanDetails = vendorPlanDetails('drip_campaigns', DripCampaign::where('vendors__id', $vendorId)->count(), $vendorId);
        if (!$vendorPlanDetails['is_limit_available']) {
            return response()->json(['reaction' => 2, 'message' => $vendorPlanDetails['message']]);
        }

        $campaign = DripCampaign::create([
            'vendors__id' => $vendorId,
            'title' => $request->title,
            'status' => 1,
        ]);

        return response()->json([
            'reaction' => 1,
            'message' => __tr('Campagne Drip créée avec succès.'),
            'data' => [
                'campaign' => [
                    '_id' => $campaign->_id,
                    '_uid' => $campaign->_uid,
                    'title' => $campaign->title,
                ],
            ],
        ]);
    }

    /**
     * Add a Step to a Campaign — clean JSON response for the mobile app
     */
    public function apiStoreStep(Request $request, $campaignId)
    {
        $request->validate([
            'delay_value' => 'required|integer|min:0',
            'delay_type' => 'required|in:minutes,hours,days',
            'whatsapp_templates__id' => 'nullable',
            'bot_replies__id' => 'nullable',
            'custom_message' => 'nullable|string',
        ]);

        $campaign = DripCampaign::where('_uid', $campaignId)
            ->where('vendors__id', getVendorId())
            ->first();

        if (!$campaign) {
            return response()->json(['reaction' => 2, 'message' => __tr('Campagne introuvable.')]);
        }

        $customMessage = $request->custom_message;
        if ($request->has('custom_message_b64')) {
            $customMessage = base64_decode($request->custom_message_b64);
        }

        $step = DripStep::create([
            'addon_drip_campaigns__id' => $campaign->_id,
            'delay_value' => $request->delay_value,
            'delay_type' => $request->delay_type,
            'whatsapp_templates__id' => $request->whatsapp_templates__id ?: null,
            'bot_replies__id' => $request->bot_replies__id ?: null,
            'custom_message' => $customMessage ?: null,
        ]);

        return response()->json([
            'reaction' => 1,
            'message' => __tr('Étape ajoutée avec succès.'),
            'data' => ['step_id' => $step->_id, 'step_uid' => $step->_uid],
        ]);
    }

    /**
     * Show the Drip Builder (Manage Steps)
     */
    public function builder($campaignId)
    {
        $vendorId = getVendorId();
        $campaign = DripCampaign::where('_uid', $campaignId)
            ->where('vendors__id', $vendorId)
            ->firstOrFail();
            
        $steps = DripStep::where('addon_drip_campaigns__id', $campaign->_id)
            ->with(['template', 'botReply'])
            ->get()
            ->sortBy(function($step) {
                $val = $step->delay_value;
                if ($step->delay_type == 'hours') return $val * 60;
                if ($step->delay_type == 'days') return $val * 1440;
                return $val;
            });
            
        $templates = WhatsAppTemplateModel::where('vendors__id', $vendorId)
            ->where('status', 'approved')
            ->get();

        $presetMessages = \App\Yantrana\Components\BotReply\Models\BotReplyModel::where('vendors__id', $vendorId)
            ->where('status', 1)
            ->where(function($q) {
                $q->whereNull('bot_flows__id')
                  ->orWhere('trigger_type', 'NT_CAMPAIGN_MESSAGE');
            })
            ->get();

        return view('WhatsJetDripCampaignAddon::builder', [
            'campaign' => $campaign,
            'steps' => $steps,
            'templates' => $templates,
            'presetMessages' => $presetMessages
        ]);
    }

    /**
     * Add a Step to a Campaign
     */
    public function storeStep(Request $request, $campaignId)
    {
        $request->validate([
            'delay_value' => 'required|integer|min:0',
            'delay_type' => 'required|in:minutes,hours,days',
            'whatsapp_templates__id' => 'nullable',
            'bot_replies__id' => 'nullable',
            'custom_message' => 'nullable|string',
        ]);

        $campaign = DripCampaign::where('_uid', $campaignId)
            ->where('vendors__id', getVendorId())
            ->firstOrFail();

        $customMessage = $request->custom_message;
        if ($request->has('custom_message_b64')) {
            $customMessage = base64_decode($request->custom_message_b64);
        }

        DripStep::create([
            'addon_drip_campaigns__id' => $campaign->_id,
            'delay_value' => $request->delay_value,
            'delay_type' => $request->delay_type,
            'whatsapp_templates__id' => $request->whatsapp_templates__id ?: null,
            'bot_replies__id' => $request->bot_replies__id ?: null,
            'custom_message' => $customMessage ?: null,
        ]);

        return back()->with('success', __tr('Step added successfully.'));
    }

    /**
     * Delete a Step
     */
    public function deleteStep($stepUid)
    {
        $step = DripStep::where('_uid', $stepUid)->firstOrFail();
        
        // Ensure the step belongs to a campaign owned by this vendor
        $campaign = DripCampaign::where('_id', $step->addon_drip_campaigns__id)
            ->where('vendors__id', getVendorId())
            ->firstOrFail();
            
        $step->delete();
        
        return back()->with('success', __tr('Step deleted successfully.'));
    }

    /**
     * Update a Step
     */
    public function updateStep(Request $request, $stepUid)
    {
        $request->validate([
            'delay_value' => 'required|integer|min:0',
            'delay_type' => 'required|in:minutes,hours,days',
            'whatsapp_templates__id' => 'nullable',
            'bot_replies__id' => 'nullable',
            'custom_message' => 'nullable|string',
        ]);

        $step = DripStep::where('_uid', $stepUid)->firstOrFail();
        
        $campaign = DripCampaign::where('_id', $step->addon_drip_campaigns__id)
            ->where('vendors__id', getVendorId())
            ->firstOrFail();

        $customMessage = $request->custom_message;
        if ($request->has('custom_message_b64')) {
            $customMessage = base64_decode($request->custom_message_b64);
        }

        $step->update([
            'delay_value' => $request->delay_value,
            'delay_type' => $request->delay_type,
            'whatsapp_templates__id' => $request->whatsapp_templates__id ?: null,
            'bot_replies__id' => $request->bot_replies__id ?: null,
            'custom_message' => $customMessage ?: null,
        ]);

        return back()->with('success', __tr('Step updated successfully.'));
    }
    
    /**
     * Delete a Campaign
     */
    public function deleteCampaign($campaignUid)
    {
        $campaign = DripCampaign::where('_uid', $campaignUid)
            ->where('vendors__id', getVendorId())
            ->firstOrFail();
            
        $campaign->delete();
        
        return back()->with('success', __tr('Campaign deleted successfully.'));
    }

    /**
     * Toggle Status of a Campaign (Active / Inactive)
     */
    public function toggleStatus($campaignUid)
    {
        $campaign = DripCampaign::where('_uid', $campaignUid)
            ->where('vendors__id', getVendorId())
            ->firstOrFail();

        $newStatus = ($campaign->status == 1) ? 2 : 1;
        $campaign->update(['status' => $newStatus]);

        $message = ($newStatus == 1)
            ? __tr('Campagne Drip activée avec succès.')
            : __tr('Campagne Drip désactivée avec succès.');

        return back()->with('success', $message);
    }
}

