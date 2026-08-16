<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VendorNotification;
use App\Yantrana\Base\BaseController;
use App\Yantrana\Components\Vendor\Models\VendorModel;
use Illuminate\Support\Facades\Auth;

class VendorNotificationController extends BaseController
{
    protected function getVendorId(Request $request)
    {
        $user = Auth::user() ?: Auth::guard('api')->user();
        if ($user && !empty($user->vendors__id)) {
            return $user->vendors__id;
        }

        $vendorUid = $request->route('vendorUid') ?: $request->get('vendorUid');
        if ($vendorUid) {
            $vendor = VendorModel::where('_uid', $vendorUid)->first();
            if ($vendor) {
                return $vendor->_id;
            }
        }

        return null;
    }

    /**
     * Get list of notifications for the logged in vendor
     */
    public function getNotifications(Request $request)
    {
        $vendorId = $this->getVendorId($request);

        $notifications = VendorNotification::where(function($query) use ($vendorId) {
            if ($vendorId) {
                $query->where('vendors__id', $vendorId)
                      ->orWhereNull('vendors__id');
            } else {
                $query->whereNull('vendors__id');
            }
        })
        ->orderBy('created_at', 'desc')
        ->limit(50)
        ->get();

        $unreadCount = $notifications->where('is_read', false)->count();

        return $this->processResponse(20, 1, [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAsRead(Request $request)
    {
        $vendorId = $this->getVendorId($request);

        VendorNotification::where(function($query) use ($vendorId) {
            if ($vendorId) {
                $query->where('vendors__id', $vendorId)
                      ->orWhereNull('vendors__id');
            } else {
                $query->whereNull('vendors__id');
            }
        })
        ->where('is_read', false)
        ->update(['is_read' => true]);

        return $this->processResponse(20, 1, [
            'message' => 'Notifications marked as read'
        ]);
    }
}
