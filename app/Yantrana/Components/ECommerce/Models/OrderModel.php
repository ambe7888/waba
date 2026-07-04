<?php
namespace App\Yantrana\Components\ECommerce\Models;

use App\Yantrana\Base\BaseModel;
use App\Yantrana\Components\Contact\Models\ContactModel;

class OrderModel extends BaseModel
{
    /**
     * @var string - The database table used by the model.
     */
    protected $table = 'orders';

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
        'contacts__id' => 'integer',
        'order_details' => 'array',
    ];

    /**
     * @var array - The attributes that are mass assignable.
     */
    protected $fillable = [
        '_uid',
        'vendors__id',
        'contacts__id',
        'order_details',
        'status',
    ];

    /**
     * Relation to contact
     */
    public function contact()
    {
        return $this->belongsTo(ContactModel::class, 'contacts__id', '_id');
    }
}
