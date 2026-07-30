<?php
namespace App\Yantrana\Components\CampaignAudience\Controllers;

use App\Yantrana\Base\BaseController;
use App\Yantrana\Base\BaseRequest;
use App\Yantrana\Components\CampaignAudience\CampaignAudienceEngine;
use App\Yantrana\Components\Contact\Models\ContactModel;
use Illuminate\Validation\Rule;

class CampaignAudienceController extends BaseController
{
    /**
     * @var CampaignAudienceEngine - CampaignAudience Engine
     */
    protected $campaignAudienceEngine;

    /**
     * Constructor
     *
     * @param CampaignAudienceEngine $campaignAudienceEngine - CampaignAudience Engine
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(CampaignAudienceEngine $campaignAudienceEngine)
    {
        $this->campaignAudienceEngine = $campaignAudienceEngine;
    }

    /**
     * Show Audience List View
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function showAudienceView()
    {
        validateVendorAccess('manage_campaigns');
        $vendorId = getVendorId();

        $groups = \App\Yantrana\Components\Contact\Models\ContactGroupModel::where('vendors__id', $vendorId)
            ->select('_id', '_uid', 'title')
            ->get();

        $labels = \App\Yantrana\Components\Contact\Models\LabelModel::where('vendors__id', $vendorId)
            ->select('_id', '_uid', 'title')
            ->get();

        return $this->loadView('campaign_audience.list', compact('groups', 'labels'));
    }

    /**
     * Prepare DataTable Data
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function prepareAudienceDataTable()
    {
        validateVendorAccess('manage_campaigns');
        return $this->campaignAudienceEngine->prepareDataTable();
    }

    /**
     * Process Add or Update Audience
     *
     * @param BaseRequest $request
     * @param string|null $audienceUid
     * @return json object
     *---------------------------------------------------------------- */
    public function processAddOrUpdate(BaseRequest $request, $audienceUid = null)
    {
        validateVendorAccess('manage_campaigns');

        $request->validate([
            'title' => [
                'required',
                'max:150',
                Rule::unique('campaign_audiences', 'title')->where(function ($query) {
                    return $query->where('vendors__id', getVendorId());
                })->ignore($audienceUid, '_uid')
            ]
        ]);

        $processReaction = $this->campaignAudienceEngine->processAddOrUpdate($request, $audienceUid);
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Process Delete Audience
     *
     * @param string $audienceUid
     * @return json object
     *---------------------------------------------------------------- */
    public function processDelete($audienceUid)
    {
        validateVendorAccess('manage_campaigns');
        $processReaction = $this->campaignAudienceEngine->processDelete($audienceUid);
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Search contacts via AJAX for Selectize remote loading
     *
     * @param BaseRequest $request
     * @return json
     *---------------------------------------------------------------- */
    public function searchContacts(BaseRequest $request)
    {
        validateVendorAccess('manage_campaigns');
        $vendorId = getVendorId();
        $search = trim($request->get('q', ''));

        $query = ContactModel::where('vendors__id', $vendorId)
            ->select('_id', 'first_name', 'last_name', 'wa_id');

        if (!empty($search)) {
            $escapedSearch = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where(function ($q) use ($escapedSearch) {
                $q->where('first_name', 'LIKE', "%{$escapedSearch}%")
                  ->orWhere('last_name', 'LIKE', "%{$escapedSearch}%")
                  ->orWhere('wa_id', 'LIKE', "%{$escapedSearch}%");
            });
        }

        $contacts = $query->orderBy('first_name')->limit(50)->get();

        return response()->json($contacts->map(function ($c) {
            return [
                'value' => (string) $c->_id,
                'text' => trim($c->first_name . ' ' . $c->last_name) . ' (+' . $c->wa_id . ')'
            ];
        }));
    }

    /**
     * Fetch specific contacts by IDs (for Selectize pre-loading on edit)
     *
     * @param BaseRequest $request
     * @return json
     *---------------------------------------------------------------- */
    public function fetchContactsByIds(BaseRequest $request)
    {
        validateVendorAccess('manage_campaigns');
        $vendorId = getVendorId();
        $ids = $request->get('ids', []);

        if (empty($ids)) {
            return response()->json([]);
        }

        $contacts = ContactModel::where('vendors__id', $vendorId)
            ->where(function ($q) use ($ids) {
                $q->whereIn('_id', $ids)
                  ->orWhereIn('_uid', $ids);
            })
            ->select('_id', 'first_name', 'last_name', 'wa_id')
            ->get();

        return response()->json($contacts->map(function ($c) {
            return [
                'value' => (string) $c->_id,
                'text' => trim($c->first_name . ' ' . $c->last_name) . ' (+' . $c->wa_id . ')'
            ];
        }));
    }
}
