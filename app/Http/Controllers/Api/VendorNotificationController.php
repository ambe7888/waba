<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VendorNotification;
use App\Yantrana\Base\BaseController;
use Illuminate\Support\Facades\Auth;

class VendorNotificationController extends BaseController
{
    /**
     * Get list of notifications for the logged in vendor
     */
    public function getNotifications(Request $request)
    {
        $user = Auth::guard('api')->user();
        if (!$user || !$user->vendors__id) {
            return $this->processResponse(20, 14, [
                'error' => 'Unauthorized or not a vendor'
            ]);
        }

        $notifications = VendorNotification::where(function($query) use ($user) {
            $query->where('vendors__id', $user->vendors__id)
                  ->orWhereNull('vendors__id'); // global notifications
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
        $user = Auth::guard('api')->user();
        if (!$user || !$user->vendors__id) {
            return $this->processResponse(20, 14, [
                'error' => 'Unauthorized or not a vendor'
            ]);
        }

        VendorNotification::where(function($query) use ($user) {
            $query->where('vendors__id', $user->vendors__id)
                  ->orWhereNull('vendors__id');
        })
        ->where('is_read', false)
        ->update(['is_read' => true]);

        return $this->processResponse(20, 1, [
            'message' => 'Notifications marked as read'
        ]);
    }
}
