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
    ];

    /**
     * @var array - Appended computed attributes
     */
    protected $appends = [
        'full_name',
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
}
