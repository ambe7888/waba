<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorNotification extends Model
{
    protected $table = 'vendor_notifications';

    protected $fillable = [
        'vendors__id',
        'title',
        'message',
        'type',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];
}
