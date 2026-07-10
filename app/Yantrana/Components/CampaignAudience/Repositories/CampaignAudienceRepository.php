<?php
namespace App\Yantrana\Components\CampaignAudience\Repositories;

use App\Yantrana\Base\BaseRepository;
use App\Yantrana\Components\CampaignAudience\Models\CampaignAudienceModel;

class CampaignAudienceRepository extends BaseRepository
{
    /**
     * Fetch the record of CampaignAudience
     *
     * @param    int || string $idOrUid
     *
     * @return    eloquent collection object
     *---------------------------------------------------------------- */
    public function fetch($idOrUid)
    {
        if (is_numeric($idOrUid)) {
            return CampaignAudienceModel::where('_id', $idOrUid)->first();
        }
        return CampaignAudienceModel::where('_uid', $idOrUid)->first();
    }

    /**
     * Fetch datatable source
     *
     * @return  mixed
     *---------------------------------------------------------------- */
    public function fetchItDataTableSource()
    {
        $dataTableConfig = [
            'searchable' => [
                'title',
            ]
        ];
        $data = CampaignAudienceModel::where('vendors__id', getVendorId())
            ->dataTables($dataTableConfig)
            ->toArray();

        if (!empty($data['data'])) {
            foreach ($data['data'] as &$row) {
                // Keep originals for edit (mapped to numeric/string values)
                $row['contacts_raw'] = $row['contacts'] ?: [];
                $row['groups_raw'] = $row['groups'] ?: [];
                $row['labels_raw'] = $row['labels'] ?: [];

                // Display formats
                $row['contacts_formatted'] = !empty($row['contacts']) ? count($row['contacts']) . ' contact(s)' : '0 contact';
                $row['groups_formatted'] = !empty($row['groups']) ? count($row['groups']) . ' groupe(s)' : '0 groupe';
                $row['labels_formatted'] = !empty($row['labels']) ? count($row['labels']) . ' étiquette(s)' : '0 étiquette';
            }
        }

        return $data;
    }

    /**
     * Store Audience
     *
     * @param array $inputData
     *
     * @return mixed
     *---------------------------------------------------------------- */
    public function storeAudience($inputData)
    {
        $audience = new CampaignAudienceModel();
        if ($audience->assignInputsAndSave($inputData, [
            'vendors__id' => getVendorId(),
            'title',
            'contacts',
            'groups',
            'labels',
            'status' => 1
        ])) {
            return $audience;
        }
        return false;
    }

    /**
     * Update Audience
     *
     * @param object $audience
     * @param array $inputData
     *
     * @return mixed
     *---------------------------------------------------------------- */
    public function updateAudience($audience, $inputData)
    {
        if ($audience->modelUpdate($inputData)) {
            return true;
        }
        return false;
    }

    /**
     * Delete Audience
     *
     * @param object $audience
     *
     * @return mixed
     *---------------------------------------------------------------- */
    public function deleteAudience($audience)
    {
        if ($audience->delete()) {
            return true;
        }
        return false;
    }
}
