<?php

namespace App\Yantrana\Components\InfoMaterial\Models;

use App\Yantrana\Base\BaseModel;
use App\Yantrana\Components\Vendor\Models\VendorModel;

class InfoMaterialModel extends BaseModel
{
    /**
     * @var string - The database table used by the model.
     */
    protected $table = 'info_materials';

    /**
     * @var array - The attributes that should be cast to native types.
     */
    protected $casts = [
        '_id' => 'integer',
        'status' => 'integer',
        'type' => 'integer',
        'vendors__id' => 'integer',
        '__data' => 'array',
    ];

    /**
     * @var array - The attributes that are mass assignable.
     */
    protected $fillable = [
        'status',
        'title',
        'description',
        'type',
        'vendors__id',
        '__data',
    ];

    /**
     * Get vendor associated with info material.
     */
    public function vendor()
    {
        return $this->belongsTo(VendorModel::class, 'vendors__id', '_id');
    }
}
