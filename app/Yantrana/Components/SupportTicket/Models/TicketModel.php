<?php

namespace App\Yantrana\Components\SupportTicket\Models;

use App\Yantrana\Base\BaseModel;
use App\Yantrana\Components\Vendor\Models\VendorUserModel;
use App\Yantrana\Components\Auth\Models\AuthModel;
use App\Yantrana\Components\Vendor\Models\VendorModel;
use App\Yantrana\Components\Contact\Models\LabelModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TicketModel extends BaseModel
{
    protected $table = 'tickets';

    protected $casts = [
        'status' => 'integer',
        '__data' => 'array',
    ];

    protected $fillable = [
        'status',
        'vendors__id',
        'subject',
        'description',
        'priority',
        'vendor_users__id',
        'assigned_users__id',
        '__data'
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorModel::class, 'vendors__id', '_id');
    }

    public function vendorUser(): BelongsTo
    {
        return $this->belongsTo(VendorUserModel::class, 'vendor_users__id', '_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(AuthModel::class, 'assigned_users__id', '_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReplyModel::class, 'tickets__id', '_id')->orderBy('created_at', 'asc');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(LabelModel::class, 'ticket_labels', 'tickets__id', 'labels__id', '_id', '_id')
            ->withTimestamps();
    }
}
