<?php
namespace App\Yantrana\Components\ECommerce\Models;

use App\Yantrana\Base\BaseModel;

class ProductCategoryModel extends BaseModel
{
    /**
     * @var string - The database table used by the model.
     */
    protected $table = 'product_categories';

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
    ];

    /**
     * @var array - The attributes that are mass assignable.
     */
    protected $fillable = [
        '_uid',
        'vendors__id',
        'name',
    ];

    /**
     * Products assigned to this category.
     */
    public function products()
    {
        return $this->hasMany(ProductModel::class, 'product_categories__id', '_id');
    }
}
