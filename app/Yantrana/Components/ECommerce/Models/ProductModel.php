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
        'product_categories__id' => 'integer',
        'price' => 'float',
        'sale_price' => 'float',
    ];

    /**
     * @var array - The attributes that are mass assignable.
     */
    protected $fillable = [
        '_uid',
        'vendors__id',
        'product_categories__id',
        'name',
        'description',
        'price',
        'sale_price',
        'image_url',
        'retailer_id',
        'direct_link',
        'source',
    ];

    /**
     * @var array - Accessors appended to the model's array/JSON form, so
     * `effective_price` reaches the frontend (product lists, the manual
     * order form) the same way any other column would.
     */
    protected $appends = ['effective_price'];

    /**
     * Category this product belongs to, if any.
     */
    public function category()
    {
        return $this->belongsTo(ProductCategoryModel::class, 'product_categories__id', '_id');
    }

    /**
     * The price actually charged -- the promo price when one is set (and
     * lower than the regular price), otherwise the regular price. Used
     * everywhere a product's price reaches a customer or an order total:
     * the AI's catalog context, the AI-driven order calculation, and the
     * WhatsApp catalog product card.
     *
     * @return float
     *---------------------------------------------------------------- */
    public function getEffectivePriceAttribute()
    {
        if (!empty($this->sale_price) && $this->sale_price > 0 && $this->sale_price < $this->price) {
            return (float) $this->sale_price;
        }
        return (float) $this->price;
    }
}
