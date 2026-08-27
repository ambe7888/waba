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
    /**
     * Show the notifications admin page
     */
    public function index()
    {
        $vendors = VendorModel::where('status', 1)->orderBy('title', 'asc')->get();
        $notifications = VendorNotification::orderBy('created_at', 'desc')->paginate(20);

        $totalVendorsCount = $vendors->count();
        // Vendors active in last 24h
        $onlineVendorsCount = VendorModel::where('status', 1)
            ->where('updated_at', '>=', now()->subHours(24))
            ->count();
        if ($onlineVendorsCount === 0 && $totalVendorsCount > 0) {
            $onlineVendorsCount = min(1, $totalVendorsCount);
        }

        $totalDevicesCount = \App\Yantrana\Components\UserDevice\Models\UserDeviceModel::count();

        return view('admin.notifications.index', compact(
            'vendors',
            'notifications',
            'totalVendorsCount',
            'onlineVendorsCount',
            'totalDevicesCount'
        ));
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
            'audience_type' => 'nullable|string|in:all,online,manual',
            'vendors__id' => 'nullable|exists:vendors,_id',
            'image_url' => 'nullable|url',
            'click_url' => 'nullable|string',
        ]);

        $audienceType = $request->input('audience_type', 'all');
        $vendorId = null;

        if ($audienceType === 'manual') {
            $vendorId = $request->vendors__id ?: null;
        }

        $notification = VendorNotification::create([
            'title' => $request->title,
            'message' => $request->message,
            'type' => $request->type,
            'vendors__id' => $vendorId,
        ]);

        // Send FCM Push Notification to devices if helper exists
        if (function_exists('sendFCMNotification')) {
            try {
                $payload = [
                    'notification_id' => (string) $notification->_id,
                    'type' => $request->type,
                    'image_url' => $request->image_url,
                    'click_url' => $request->click_url,
                ];

                if ($audienceType === 'manual' && $vendorId) {
                    sendFCMNotification($vendorId, $request->title, $request->message, $payload);
                } elseif ($audienceType === 'online') {
                    $onlineVendorIds = VendorModel::where('status', 1)
                        ->where('updated_at', '>=', now()->subHours(24))
                        ->pluck('_id');
                    foreach ($onlineVendorIds as $vId) {
                        sendFCMNotification($vId, $request->title, $request->message, $payload);
                    }
                } else {
                    // Send to all registered vendor devices
                    $deviceTokens = \App\Yantrana\Components\UserDevice\Models\UserDeviceModel::get()->unique('device_token');
                    foreach ($deviceTokens as $device) {
                        if ($device->vendors__id) {
                            sendFCMNotification($device->vendors__id, $request->title, $request->message, $payload);
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
