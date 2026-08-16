<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VendorNotification;
use App\Yantrana\Components\Vendor\Models\VendorModel;
use App\Yantrana\Base\BaseController;

class NotificationController extends BaseController
{
    /**
     * Show the notifications admin page
     */
    public function index()
    {
        $vendors = VendorModel::where('status', 1)->get();
        $notifications = VendorNotification::orderBy('created_at', 'desc')->paginate(20);

        return view('admin.notifications.index', compact('vendors', 'notifications'));
    }

    /**
     * Store and send a new notification
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|string|in:info,success,warning,danger',
            'vendors__id' => 'nullable|exists:vendors,_id',
        ]);

        $vendorId = $request->vendors__id ?: null;

        $notification = VendorNotification::create([
            'title' => $request->title,
            'message' => $request->message,
            'type' => $request->type,
            'vendors__id' => $vendorId,
        ]);

        // Send FCM Push Notification to devices if helper exists
        if (function_exists('sendFCMNotification')) {
            try {
                if ($vendorId) {
                    sendFCMNotification($vendorId, $request->title, $request->message, [
                        'notification_id' => (string) $notification->_id,
                        'type' => $request->type,
                    ]);
                } else {
                    $deviceTokens = \App\Yantrana\Components\UserDevice\Models\UserDeviceModel::get()->unique('device_token');
                    foreach ($deviceTokens as $device) {
                        if ($device->vendors__id) {
                            sendFCMNotification($device->vendors__id, $request->title, $request->message, [
                                'notification_id' => (string) $notification->_id,
                                'type' => $request->type,
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error('NotificationController FCM Error: ' . $e->getMessage());
            }
        }

        return back()->with([
            'message' => 'Notification envoyée avec succès',
            'messageType' => 'success'
        ]);
    }
}
