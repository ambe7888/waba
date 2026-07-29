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
        $vendorId = getVendorId();
        $data = CampaignAudienceModel::where('vendors__id', $vendorId)
            ->dataTables($dataTableConfig)
            ->toArray();

        if (!empty($data['data'])) {
            // Preload group and label titles for display
            $allGroupIds = [];
            $allLabelIds = [];
            foreach ($data['data'] as $row) {
                if (!empty($row['groups'])) {
                    $allGroupIds = array_merge($allGroupIds, $row['groups']);
                }
                if (!empty($row['labels'])) {
                    $allLabelIds = array_merge($allLabelIds, $row['labels']);
                }
            }
            $groupTitles = [];
            $labelTitles = [];
            if (!empty($allGroupIds)) {
                $groupTitles = \App\Yantrana\Components\Contact\Models\ContactGroupModel::whereIn('_id', array_unique($allGroupIds))
                    ->pluck('title', '_id')->toArray();
            }
            if (!empty($allLabelIds)) {
                $labelTitles = \App\Yantrana\Components\Contact\Models\LabelModel::whereIn('_id', array_unique($allLabelIds))
                    ->pluck('title', '_id')->toArray();
            }

            foreach ($data['data'] as &$row) {
                // Keep originals for edit (mapped to numeric/string values)
                $row['contacts_raw'] = $row['contacts'] ?: [];
                $row['groups_raw'] = $row['groups'] ?: [];
                $row['labels_raw'] = $row['labels'] ?: [];

                $isAllContacts = in_array('all_contacts', $row['contacts'] ?: []);
                $row['is_all_contacts'] = $isAllContacts;

                if ($isAllContacts) {
                    $realCount = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)->count();
                    $row['contacts_formatted'] = $realCount . ' contact(s) (⚡ Tous les contacts)';
                    $row['groups_formatted'] = '-';
                    $row['labels_formatted'] = '-';
                    continue;
                }

                // Calculate real targeted contact count with deduplication
                $contactIds = collect($row['contacts'] ?: []);
                $groupContactIds = collect();
                $labelContactIds = collect();

                if (!empty($row['groups'])) {
                    $groupContactIds = \Illuminate\Support\Facades\DB::table('contact_groups_contacts')
                        ->whereIn('contact_groups__id', $row['groups'])
                        ->pluck('contacts__id');
                }
                if (!empty($row['labels'])) {
                    $labelContactIds = \Illuminate\Support\Facades\DB::table('contact_labels')
                        ->whereIn('labels__id', $row['labels'])
                        ->pluck('contacts__id');
                }

                // Merge all and deduplicate
                $allContactIds = $contactIds->merge($groupContactIds)->merge($labelContactIds)->unique();
                // Only count contacts that actually exist for this vendor
                if ($allContactIds->isNotEmpty()) {
                    $realCount = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)
                        ->whereIn('_id', $allContactIds->toArray())
                        ->count();
                } else {
                    $realCount = 0;
                }

                $row['contacts_formatted'] = $realCount . ' contact(s)';

                // Display group/label names
                $groupNames = [];
                foreach (($row['groups'] ?: []) as $gid) {
                    $groupNames[] = $groupTitles[$gid] ?? '#' . $gid;
                }
                $row['groups_formatted'] = !empty($groupNames) ? implode(', ', $groupNames) : '0 groupe';

                $labelNames = [];
                foreach (($row['labels'] ?: []) as $lid) {
                    $labelNames[] = $labelTitles[$lid] ?? '#' . $lid;
                }
                $row['labels_formatted'] = !empty($labelNames) ? implode(', ', $labelNames) : '0 étiquette';
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
