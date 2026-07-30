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
            // ── Pre-collect ALL group/label IDs across all rows ──
            $allGroupIds = [];
            $allLabelIds = [];
            $allDirectContactIds = [];
            $hasAllContactsRows = false;

            foreach ($data['data'] as $row) {
                if (in_array('all_contacts', $row['contacts'] ?: [])) {
                    $hasAllContactsRows = true;
                    continue;
                }
                if (!empty($row['groups'])) {
                    $allGroupIds = array_merge($allGroupIds, $row['groups']);
                }
                if (!empty($row['labels'])) {
                    $allLabelIds = array_merge($allLabelIds, $row['labels']);
                }
                if (!empty($row['contacts'])) {
                    $filteredContacts = array_filter($row['contacts'], fn($c) => $c !== 'all_contacts');
                    $allDirectContactIds = array_merge($allDirectContactIds, $filteredContacts);
                }
            }

            // ── Batch fetch group titles ──
            $groupTitles = [];
            if (!empty($allGroupIds)) {
                $groupTitles = \App\Yantrana\Components\Contact\Models\ContactGroupModel::whereIn('_id', array_unique($allGroupIds))
                    ->pluck('title', '_id')->toArray();
            }

            // ── Batch fetch label titles ──
            $labelTitles = [];
            if (!empty($allLabelIds)) {
                $labelTitles = \App\Yantrana\Components\Contact\Models\LabelModel::whereIn('_id', array_unique($allLabelIds))
                    ->pluck('title', '_id')->toArray();
            }

            // ── Batch fetch group→contact mappings ──
            $groupContactMap = [];
            if (!empty($allGroupIds)) {
                $groupContacts = \Illuminate\Support\Facades\DB::table('contact_groups_contacts')
                    ->whereIn('contact_groups__id', array_unique($allGroupIds))
                    ->select('contact_groups__id', 'contacts__id')
                    ->get();
                foreach ($groupContacts as $gc) {
                    $groupContactMap[$gc->contact_groups__id][] = $gc->contacts__id;
                }
            }

            // ── Batch fetch label→contact mappings ──
            $labelContactMap = [];
            if (!empty($allLabelIds)) {
                $labelContacts = \Illuminate\Support\Facades\DB::table('contact_labels')
                    ->whereIn('labels__id', array_unique($allLabelIds))
                    ->select('labels__id', 'contacts__id')
                    ->get();
                foreach ($labelContacts as $lc) {
                    $labelContactMap[$lc->labels__id][] = $lc->contacts__id;
                }
            }

            // ── Cache "all contacts" count (only once if needed) ──
            $allContactsCount = null;
            if ($hasAllContactsRows) {
                $allContactsCount = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)->count();
            }

            // ── Batch validate that direct contact IDs exist ──
            $validContactIds = [];
            if (!empty($allDirectContactIds)) {
                $validContactIds = \App\Yantrana\Components\Contact\Models\ContactModel::where('vendors__id', $vendorId)
                    ->whereIn('_id', array_unique($allDirectContactIds))
                    ->pluck('_id')->toArray();
            }

            // ── Now process each row using cached data (ZERO additional queries) ──
            foreach ($data['data'] as &$row) {
                // Keep originals for edit (mapped to numeric/string values)
                $row['contacts_raw'] = $row['contacts'] ?: [];
                $row['groups_raw'] = $row['groups'] ?: [];
                $row['labels_raw'] = $row['labels'] ?: [];

                $isAllContacts = in_array('all_contacts', $row['contacts'] ?: []);
                $row['is_all_contacts'] = $isAllContacts;

                if ($isAllContacts) {
                    $row['contacts_formatted'] = $allContactsCount . ' contact(s) (⚡ Tous les contacts)';
                    $row['groups_formatted'] = '-';
                    $row['labels_formatted'] = '-';
                    continue;
                }

                // Calculate real targeted contact count with deduplication from cached data
                $contactIds = collect(array_intersect($row['contacts'] ?: [], $validContactIds));

                $groupContactIds = collect();
                foreach (($row['groups'] ?: []) as $gid) {
                    if (isset($groupContactMap[$gid])) {
                        $groupContactIds = $groupContactIds->merge($groupContactMap[$gid]);
                    }
                }

                $labelContactIds = collect();
                foreach (($row['labels'] ?: []) as $lid) {
                    if (isset($labelContactMap[$lid])) {
                        $labelContactIds = $labelContactIds->merge($labelContactMap[$lid]);
                    }
                }

                // Merge all and deduplicate
                $allTargetContactIds = $contactIds->merge($groupContactIds)->merge($labelContactIds)->unique();
                $realCount = $allTargetContactIds->count();

                $row['contacts_formatted'] = $realCount . ' contact(s)';

                // Display group/label names from cached titles
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
