<?php
namespace App\Yantrana\Components\Pipeline\Repositories;

use App\Yantrana\Base\BaseRepository;
use App\Yantrana\Components\Pipeline\Models\DealModel;

class DealRepository extends BaseRepository
{
    /**
     * @var string
     */
    protected $primaryModel = DealModel::class;

    /**
     * Fetch every open deal for a vendor, with the contact and assigned
     * user eager-loaded, for the Kanban board. Left flat (not grouped by
     * stage here) -- the board groups client-side by pipeline_stages__id,
     * which is simpler than serializing a collection-of-collections.
     *
     * @param int $vendorId
     * @return \Illuminate\Support\Collection
     *---------------------------------------------------------------- */
    public function fetchBoardDeals($vendorId)
    {
        return DealModel::with(['contact:_id,_uid,first_name,last_name,wa_id', 'assignedUser:_id,first_name,last_name'])
            ->where('vendors__id', $vendorId)
            ->orderBy('position')
            ->get();
    }

    /**
     * Fetch a deal by uid, scoped to a vendor.
     *
     * @param string $uid
     * @param int $vendorId
     * @return DealModel|null
     *---------------------------------------------------------------- */
    public function fetchByUid($uid, $vendorId)
    {
        return DealModel::where(['vendors__id' => $vendorId, '_uid' => $uid])->first();
    }

    /**
     * Deals linked to one contact (used on the contact info sidebar).
     *
     * @param int $contactId
     * @param int $vendorId
     * @return \Illuminate\Support\Collection
     *---------------------------------------------------------------- */
    public function fetchForContact($contactId, $vendorId)
    {
        return DealModel::with('stage:_id,title,color,is_won,is_lost')
            ->where(['vendors__id' => $vendorId, 'contacts__id' => $contactId])
            ->latest()
            ->get();
    }
}
