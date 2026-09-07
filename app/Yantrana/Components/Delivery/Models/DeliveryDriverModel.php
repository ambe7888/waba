<?php
namespace App\Yantrana\Components\Delivery\Models;

use App\Yantrana\Base\BaseModel;
use App\Yantrana\Components\ECommerce\Models\OrderModel;

class DeliveryDriverModel extends BaseModel
{
    /**
     * @var string - The database table used by the model.
     */
    protected $table = 'delivery_drivers';

    /**
     * @var string - Primary Key
     */
    protected $primaryKey = '_id';

    /**
     * @var array - The attributes that should be casted to native types.
     */
    protected $casts = [
        '_id' => 'integer',
        'vendors__id' => 'integer',
        'is_active' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    /**
     * @var array - The attributes that are mass assignable.
     */
    protected $fillable = [
        '_uid',
        'vendors__id',
        'first_name',
        'last_name',
        'zone',
        'phone',
        'address',
        'vehicle_type',
        'is_active',
        'last_message_at',
    ];

    /**
     * @var array - Appended computed attributes
     */
    protected $appends = [
        'full_name',
        'is_24h_window_open',
    ];

    /**
     * Orders currently assigned to this driver
     */
    public function orders()
    {
        return $this->hasMany(OrderModel::class, 'assigned_driver__id', '_id');
    }

    /**
     * Full name accessor
     */
    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Whether WhatsApp's 24h customer-service window is currently open for
     * this driver (i.e. a free-form/interactive message can actually be
     * delivered, as opposed to a pre-approved template).
     */
    public function getIs24hWindowOpenAttribute()
    {
        return !empty($this->last_message_at) && $this->last_message_at->diffInHours(now()) < 24;
    }
}
