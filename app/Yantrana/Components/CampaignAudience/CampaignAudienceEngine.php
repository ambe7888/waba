<?php
namespace App\Yantrana\Components\CampaignAudience;

use App\Yantrana\Base\BaseEngine;
use App\Yantrana\Components\CampaignAudience\Repositories\CampaignAudienceRepository;

class CampaignAudienceEngine extends BaseEngine
{
    /**
     * @var CampaignAudienceRepository - CampaignAudience Repository
     */
    protected $campaignAudienceRepository;

    /**
     * Constructor
     *
     * @param CampaignAudienceRepository $campaignAudienceRepository - CampaignAudience Repository
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(CampaignAudienceRepository $campaignAudienceRepository)
    {
        $this->campaignAudienceRepository = $campaignAudienceRepository;
    }

    /**
     * Prepare datatable data
     *
     * @return array
     *---------------------------------------------------------------- */
    public function prepareDataTable()
    {
        return $this->campaignAudienceRepository->fetchItDataTableSource();
    }

    /**
     * Process add or update audience
     *
     * @param object $request
     * @param string|null $audienceUid
     * @return array
     *---------------------------------------------------------------- */
    public function processAddOrUpdate($request, $audienceUid = null)
    {
        $inputData = $request->all();
        $inputData['contacts'] = $request->contacts ?: [];
        $inputData['groups'] = $request->groups ?: [];
        $inputData['labels'] = $request->labels ?: [];

        if ($audienceUid) {
            $audience = $this->campaignAudienceRepository->fetch($audienceUid);
            if (__isEmpty($audience)) {
                return $this->engineResponse(18, null, __tr('Audience not found.'));
            }
            if ($this->campaignAudienceRepository->updateAudience($audience, $inputData)) {
                return $this->engineResponse(1, null, __tr('Audience updated successfully.'));
            }
            return $this->engineResponse(2, null, __tr('Audience not updated.'));
        }

        if ($this->campaignAudienceRepository->storeAudience($inputData)) {
            return $this->engineResponse(1, null, __tr('Audience added successfully.'));
        }
        return $this->engineResponse(2, null, __tr('Audience not added.'));
    }

    /**
     * Process delete audience
     *
     * @param string $audienceUid
     * @return array
     *---------------------------------------------------------------- */
    public function processDelete($audienceUid)
    {
        $audience = $this->campaignAudienceRepository->fetch($audienceUid);
        if (__isEmpty($audience)) {
            return $this->engineResponse(18, null, __tr('Audience not found.'));
        }
        if ($this->campaignAudienceRepository->deleteAudience($audience)) {
            return $this->engineResponse(1, null, __tr('Audience deleted successfully.'));
        }
        return $this->engineResponse(2, null, __tr('Audience not deleted.'));
    }
}
