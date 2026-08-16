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
            'vendors__id' => 'nullable|exists:vendors,id',
        ]);

        VendorNotification::create([
            'title' => $request->title,
            'message' => $request->message,
            'type' => $request->type,
            'vendors__id' => $request->vendors__id ?: null,
        ]);

        return back()->with([
            'message' => 'Notification envoyée avec succès',
            'messageType' => 'success'
        ]);
    }
}
