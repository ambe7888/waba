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
     * Show the Drip Builder (Manage Steps)
     */
    public function builder($campaignId)
    {
        $vendorId = getVendorId();
        $campaign = DripCampaign::where('_uid', $campaignId)
            ->where('vendors__id', $vendorId)
            ->firstOrFail();
            
        $steps = DripStep::where('addon_drip_campaigns__id', $campaign->_id)
            ->with('template')
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

        return view('WhatsJetDripCampaignAddon::builder', [
            'campaign' => $campaign,
            'steps' => $steps,
            'templates' => $templates
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
            'whatsapp_templates__id' => 'required_without:custom_message',
        ]);

        $campaign = DripCampaign::where('_uid', $campaignId)
            ->where('vendors__id', getVendorId())
            ->firstOrFail();

        DripStep::create([
            'addon_drip_campaigns__id' => $campaign->_id,
            'delay_value' => $request->delay_value,
            'delay_type' => $request->delay_type,
            'whatsapp_templates__id' => $request->whatsapp_templates__id,
            'custom_message' => $request->custom_message,
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
}
