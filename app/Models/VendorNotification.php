<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorNotification extends Model
{
    protected $table = 'vendor_notifications';

    protected $fillable = [
        '_uid',
        'vendors__id',
        'title',
        'message',
        'type',
        'is_read',
        'action',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($notification) {
            if (empty($notification->_uid)) {
                $notification->_uid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function vendor()
    {
        return $this->belongsTo(\App\Yantrana\Components\Vendor\Models\VendorModel::class, 'vendors__id', '_id');
    }
}
