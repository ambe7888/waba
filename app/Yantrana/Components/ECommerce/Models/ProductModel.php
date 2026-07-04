<?php
namespace App\Yantrana\Components\ECommerce\Models;

use App\Yantrana\Base\BaseModel;

class ProductModel extends BaseModel
{
    /**
     * @var string - The database table used by the model.
     */
    protected $table = 'products';

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
        'price' => 'float',
    ];

    /**
     * @var array - The attributes that are mass assignable.
     */
    protected $fillable = [
        '_uid',
        'vendors__id',
        'name',
        'description',
        'price',
        'image_url',
        'retailer_id',
        'direct_link',
        'source',
    ];
}
