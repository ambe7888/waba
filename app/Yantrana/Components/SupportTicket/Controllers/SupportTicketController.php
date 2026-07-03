<?php

namespace App\Yantrana\Components\SupportTicket\Controllers;

use App\Yantrana\Base\BaseController;
use App\Yantrana\Components\SupportTicket\Models\TicketModel;
use App\Yantrana\Components\SupportTicket\Models\TicketReplyModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class SupportTicketController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // For superadmin, show all tickets. For vendor, show only their tickets.
        $query = TicketModel::with(['vendor', 'vendorUser.user', 'assignedUser', 'labels']);

        if (hasCentralAccess()) {
            // Central Admin sees all tickets
        } else {
            $vendorId = getVendorId();
            $query->where('vendors__id', $vendorId);
        }

        // Apply Search/Filters
        if ($request->filled('status')) {
            $query->where('status', intval($request->status));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', $search)
                  ->orWhere('description', 'like', $search)
                  ->orWhere('_uid', 'like', $search);
            });
        }

        if ($request->filled('label')) {
            $labelId = $request->label;
            $query->whereHas('labels', function ($q) use ($labelId) {
                $q->where('labels._id', $labelId);
            });
        }

        $tickets = $query->orderBy('updated_at', 'desc')->paginate(20)->withQueryString();

        // Get global labels for filtering
        $labels = \App\Yantrana\Components\Contact\Models\LabelModel::whereNull('vendors__id')->get();

        return $this->loadView('support_ticket.index', compact('tickets', 'labels'));
    }

    /**
     * API: Display a listing of the resource for Mobile App.
     */
    public function apiIndex(Request $request)
    {
        // For superadmin, show all tickets. For vendor, show only their tickets.
        $query = TicketModel::with(['vendor', 'vendorUser.user', 'assignedUser', 'labels']);

        if (hasCentralAccess()) {
            // Central Admin sees all tickets
        } else {
            $vendorId = getVendorId();
            $query->where('vendors__id', $vendorId);
        }

        // Apply Search/Filters
        if ($request->filled('status')) {
            $query->where('status', intval($request->status));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', $search)
                  ->orWhere('description', 'like', $search)
                  ->orWhere('_uid', 'like', $search);
            });
        }

        $tickets = $query->orderBy('updated_at', 'desc')->paginate(20)->withQueryString();

        return $this->processResponse(1, [], [
            'tickets' => $tickets->items(),
            'current_page' => $tickets->currentPage(),
            'last_page' => $tickets->lastPage(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */

    public function apiStore(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:150',
            'description' => 'required|string',
            'priority' => 'nullable|string|in:low,normal,high',
        ]);

        $vendorId = getVendorId();

        $vendorUser = \App\Yantrana\Components\Vendor\Models\VendorUserModel::where([
            'vendors__id' => $vendorId,
            'users__id' => Auth::id()
        ])->first();

        $ticket = TicketModel::create([
            'status' => 1,
            'vendors__id' => $vendorId,
            'subject' => $request->subject,
            'description' => $request->description,
            'priority' => $request->priority ?? 'normal',
            'vendor_users__id' => $vendorUser ? $vendorUser->_id : null,
            'contacts__id' => null,
            '__data' => null,
        ]);

        return $this->processResponse(1, [
            1 => 'Ticket created successfully.'
        ], [
            'ticket' => $ticket
        ]);
    }

    /**
     * API: Display the specified resource for Mobile App.
     */
    public function apiShow($uid)
    {
        $ticketQuery = TicketModel::with(['vendor', 'vendorUser.user', 'replies.user', 'assignedUser', 'labels'])->where('_uid', $uid);
        
        if (!hasCentralAccess()) {
            $ticketQuery->where('vendors__id', getVendorId());
        }

        $ticket = $ticketQuery->firstOrFail();

        return $this->processResponse(1, [], [
            'ticket' => $ticket
        ]);
    }

    /**
     * API: Add reply to ticket for Mobile App.
     */
    public function apiReply(Request $request, $uid)
    {
        $request->validate([
            'message' => 'required|string',
            'attachment' => 'nullable|file|max:10240', // 10MB max
        ]);

        $ticketQuery = TicketModel::where('_uid', $uid);
        if (!hasCentralAccess()) {
            $ticketQuery->where('vendors__id', getVendorId());
        }
        $ticket = $ticketQuery->firstOrFail();

        $fileData = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('support_tickets', $filename, 'public');
            $fileData = [
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName()
            ];
        }

        TicketReplyModel::create([
            'tickets__id' => $ticket->_id,
            'users__id' => Auth::id(),
            'message' => $request->message,
            '__data' => $fileData ? ['attachment' => $fileData] : null,
        ]);

        // Update ticket updated_at and status if needed
        $ticket->touch();
        if (hasCentralAccess()) {
            $ticket->status = 2; // 2: In Progress / Answered
            if (!$ticket->assigned_users__id) {
                $ticket->assigned_users__id = Auth::id();
            }

            // Send push notification to vendor
            if (function_exists('sendFCMNotification')) {
                sendFCMNotification(
                    $ticket->vendors__id,
                    "Réponse: " . $ticket->subject,
                    \Illuminate\Support\Str::limit(strip_tags($request->message), 100),
                    [
                        'type' => 'support_ticket',
                        'uid' => $ticket->_uid
                    ]
                );
            }
        } else {
            // Vendor replied, reopen ticket if it was answered (2)
            if ($ticket->status == 2) {
                $ticket->status = 1;
            }
        }
        $ticket->save();

        return $this->processResponse(1, [
            1 => 'Reply added successfully.'
        ]);
    }

    public function create()
    {
        return $this->loadView('support_ticket.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:150',
            'description' => 'required|string',
            'priority' => 'nullable|string|in:low,normal,high',
            'attachment' => 'nullable|file|max:10240', // 10MB max
        ]);

        $vendorId = getVendorId();

        // Find the vendor user ID from vendor_users table
        $vendorUser = \App\Yantrana\Components\Vendor\Models\VendorUserModel::where([
            'vendors__id' => $vendorId,
            'users__id' => Auth::id()
        ])->first();

        $fileData = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('support_tickets', $filename, 'public');
            $fileData = [
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName()
            ];
        }
        
        $ticket = TicketModel::create([
            'status' => 1, // 1: Open
            'vendors__id' => $vendorId,
            'subject' => $request->subject,
            'description' => $request->description,
            'priority' => $request->priority ?? 'normal',
            'vendor_users__id' => $vendorUser ? $vendorUser->_id : null,
            'contacts__id' => null,
            '__data' => $fileData ? ['attachment' => $fileData] : null,
        ]);

        return redirect()->route('support_ticket.show', $ticket->_uid)->with('success', 'Ticket created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($uid)
    {
        $ticketQuery = TicketModel::with(['vendor', 'vendorUser.user', 'replies.user', 'assignedUser', 'labels'])->where('_uid', $uid);
        
        if (!hasCentralAccess()) {
            $ticketQuery->where('vendors__id', getVendorId());
        }

        $ticket = $ticketQuery->firstOrFail();

        // Get list of admin users (central superadmins) for assignee dropdown
        $admins = [];
        if (hasCentralAccess()) {
            $admins = \App\Yantrana\Components\Auth\Models\AuthModel::where('user_roles__id', 1)
                ->where('status', 1)
                ->get();
        }

        return $this->loadView('support_ticket.show', compact('ticket', 'admins'));
    }

    /**
     * Add reply to ticket.
     */
    public function reply(Request $request, $uid)
    {
        $request->validate([
            'message' => 'required|string',
            'attachment' => 'nullable|file|max:10240', // 10MB max
        ]);

        $ticketQuery = TicketModel::where('_uid', $uid);
        if (!hasCentralAccess()) {
            $ticketQuery->where('vendors__id', getVendorId());
        }
        $ticket = $ticketQuery->firstOrFail();

        $fileData = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('support_tickets', $filename, 'public');
            $fileData = [
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName()
            ];
        }

        TicketReplyModel::create([
            'tickets__id' => $ticket->_id,
            'users__id' => Auth::id(),
            'message' => $request->message,
            '__data' => $fileData ? ['attachment' => $fileData] : null,
        ]);

        // Update ticket updated_at and status if needed
        $ticket->touch();
        if (hasCentralAccess()) {
            $ticket->status = 2; // 2: In Progress / Answered
            if (!$ticket->assigned_users__id) {
                $ticket->assigned_users__id = Auth::id();
            }

            // Send push notification to vendor
            if (function_exists('sendFCMNotification')) {
                sendFCMNotification(
                    $ticket->vendors__id,
                    "Réponse: " . $ticket->subject,
                    \Illuminate\Support\Str::limit(strip_tags($request->message), 100),
                    [
                        'type' => 'support_ticket',
                        'uid' => $ticket->_uid
                    ]
                );
            }
        } else {
            // Vendor replied, reopen ticket if it was answered (2)
            if ($ticket->status == 2) {
                $ticket->status = 1;
            }
        }
        $ticket->save();

        return redirect()->back()->with('success', 'Reply added successfully.');
    }

    /**
     * Update ticket status.
     */
    public function updateStatus(Request $request, $uid)
    {
        $request->validate([
            'status' => 'required|integer|in:1,2,3,4',
        ]);

        $ticket = TicketModel::where('_uid', $uid)->firstOrFail();
        
        // Only superadmin can change status freely, or vendor can resolve/close it
        if (hasCentralAccess() || ($ticket->vendors__id == getVendorId())) {
            $ticket->status = $request->status;
            $ticket->save();
            return redirect()->back()->with('success', 'Ticket status updated.');
        }

        abort(403);
    }

    /**
     * Update ticket priority.
     */
    public function updatePriority(Request $request, $uid)
    {
        abortIf(!hasCentralAccess(), 403);

        $request->validate([
            'priority' => 'required|string|in:low,normal,high',
        ]);

        $ticket = TicketModel::where('_uid', $uid)->firstOrFail();
        $ticket->priority = $request->priority;
        $ticket->save();

        return redirect()->back()->with('success', 'Ticket priority updated.');
    }

    /**
     * Assign ticket to admin.
     */
    public function assignTicket(Request $request, $uid)
    {
        abortIf(!hasCentralAccess(), 403);

        $request->validate([
            'assigned_users__id' => 'nullable|integer',
        ]);

        $ticket = TicketModel::where('_uid', $uid)->firstOrFail();
        
        if ($request->assigned_users__id) {
            $userExists = \App\Yantrana\Components\Auth\Models\AuthModel::where('_id', $request->assigned_users__id)
                ->where('user_roles__id', 1)
                ->exists();
            if (!$userExists) {
                return redirect()->back()->with('error', 'Invalid assignee.');
            }
            $ticket->assigned_users__id = $request->assigned_users__id;
        } else {
            $ticket->assigned_users__id = null;
        }
        
        $ticket->save();

        return redirect()->back()->with('success', 'Ticket assignee updated.');
    }

    /**
     * Update ticket labels.
     */
    public function updateLabels(Request $request, $uid)
    {
        abortIf(!hasCentralAccess(), 403);

        $request->validate([
            'labels' => 'nullable|array',
            'labels.*' => 'string'
        ]);

        $ticket = TicketModel::where('_uid', $uid)->firstOrFail();
        
        $labelIds = [];
        if ($request->has('labels')) {
            foreach ($request->labels as $labelName) {
                // Find or create label
                $label = \App\Yantrana\Components\Contact\Models\LabelModel::firstOrCreate([
                    'title' => trim($labelName),
                    'vendors__id' => null, // Global label
                ], [
                    'status' => 1
                ]);
                
                $labelIds[] = $label->_id;
            }
        }

        $ticket->labels()->sync($labelIds);

        return redirect()->back()->with('success', 'Ticket labels updated.');
    }

    /**
     * Download support ticket attachment.
     */
    public function downloadAttachment($type, $uid)
    {
        if ($type === 'ticket') {
            $model = TicketModel::where('_uid', $uid)->firstOrFail();
            if (!hasCentralAccess() && $model->vendors__id != getVendorId()) {
                abort(403);
            }
        } elseif ($type === 'reply') {
            $reply = TicketReplyModel::where('_uid', $uid)->firstOrFail();
            $model = TicketModel::findOrFail($reply->tickets__id);
            if (!hasCentralAccess() && $model->vendors__id != getVendorId()) {
                abort(403);
            }
        } else {
            abort(404);
        }

        $data = ($type === 'ticket') ? ($model->__data ?? []) : ($reply->__data ?? []);

        if (empty($data['attachment']['file_path'])) {
            abort(404, __tr('No attachment found.'));
        }

        $path = storage_path('app/public/' . $data['attachment']['file_path']);

        if (!file_exists($path)) {
            abort(404, __tr('File not found.'));
        }

        return response()->download($path, $data['attachment']['file_name']);
    }
}
