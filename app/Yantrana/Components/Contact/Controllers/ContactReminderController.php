<?php
/**
* ContactReminderController.php - Controller file
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Contact\Controllers;

use App\Yantrana\Base\BaseController;
use App\Yantrana\Base\BaseRequestTwo;
use App\Yantrana\Components\Contact\ContactReminderEngine;

class ContactReminderController extends BaseController
{
    /**
     * @var ContactReminderEngine $contactReminderEngine
     */
    protected $contactReminderEngine;

    /**
     * Constructor
     */
    public function __construct(ContactReminderEngine $contactReminderEngine)
    {
        $this->contactReminderEngine = $contactReminderEngine;
    }

    /**
     * Store contact reminder
     *
     * @param BaseRequestTwo $request
     * @param string $contactUid
     * @return json
     */
    public function storeReminder(BaseRequestTwo $request, $contactUid)
    {
        validateVendorAccess('messaging');

        $request->validate([
            'preset_time' => 'required|string',
            'action_type' => 'required|string|in:notification,auto_message',
            'title_note' => 'required|string|max:1000',
        ]);

        $processReaction = $this->contactReminderEngine->processCreateReminder($request->all(), $contactUid);

        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Cancel contact reminder
     *
     * @param string $contactUid
     * @return json
     */
    public function cancelReminder($contactUid)
    {
        validateVendorAccess('messaging');

        $processReaction = $this->contactReminderEngine->processCancelReminder($contactUid);

        return $this->processResponse($processReaction, [], [], true);
    }
}
