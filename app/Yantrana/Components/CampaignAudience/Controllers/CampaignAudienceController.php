<?php
namespace App\Yantrana\Components\CampaignAudience\Controllers;

use App\Yantrana\Base\BaseController;
use App\Yantrana\Base\BaseRequest;
use App\Yantrana\Components\CampaignAudience\CampaignAudienceEngine;
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
        return $this->loadView('campaign_audience.list');
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
}
