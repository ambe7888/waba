<?php
/**
* ContactReminderRepository.php - Repository file
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Contact\Repositories;

use App\Yantrana\Base\BaseRepository;
use App\Yantrana\Components\Contact\Models\ContactReminderModel;

class ContactReminderRepository extends BaseRepository
{
    /**
     * @var ContactReminderModel $primaryModel - Primary Model Instance
     */
    protected $primaryModel = ContactReminderModel::class;

    /**
     * Fetch active pending reminder for contact
     *
     * @param int $contactId
     * @param int $vendorId
     * @return object|null
     */
    public function fetchActiveReminderForContact($contactId, $vendorId)
    {
        return ContactReminderModel::where('contacts__id', $contactId)
            ->where('vendors__id', $vendorId)
            ->where('status', 1) // 1 = pending
            ->orderBy('scheduled_at', 'asc')
            ->first();
    }

    /**
     * Fetch all active pending reminders for vendor contacts
     *
     * @param int $vendorId
     * @return \Illuminate\Support\Collection
     */
    public function fetchActiveRemindersForVendor($vendorId)
    {
        return ContactReminderModel::where('vendors__id', $vendorId)
            ->where('status', 1)
            ->get();
    }

    /**
     * Fetch due reminders ready for execution
     *
     * @return \Illuminate\Support\Collection
     */
    public function fetchDueReminders()
    {
        return ContactReminderModel::where('status', 1)
            ->where('scheduled_at', '<=', now())
            ->get();
    }
}
