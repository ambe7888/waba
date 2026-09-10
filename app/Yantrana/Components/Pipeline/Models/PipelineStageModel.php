<?php
/**
* PipelineStageModel.php - Model file
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Pipeline\Models;

use App\Yantrana\Base\BaseModel;

class PipelineStageModel extends BaseModel
{
    /**
     * @var string - The database table used by the model.
     */
    protected $table = 'pipeline_stages';

    /**
     * @var array - The attributes that should be casted to native types.
     */
    protected $casts = [
        '_id' => 'integer',
        'vendors__id' => 'integer',
        'position' => 'integer',
        'is_won' => 'boolean',
        'is_lost' => 'boolean',
    ];

    /**
     * @var array - The attributes that are mass assignable.
     */
    protected $fillable = [
        '_uid',
        'vendors__id',
        'title',
        'position',
        'color',
        'is_won',
        'is_lost',
    ];

    /**
     * Deals in this stage.
     */
    public function deals()
    {
        return $this->hasMany(DealModel::class, 'pipeline_stages__id', '_id');
    }
}
