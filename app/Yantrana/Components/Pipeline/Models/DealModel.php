<?php
/**
* DealModel.php - Model file
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Pipeline\Models;

use App\Yantrana\Base\BaseModel;
use App\Yantrana\Components\Contact\Models\ContactModel;
use App\Yantrana\Components\Auth\Models\AuthModel;

class DealModel extends BaseModel
{
    /**
     * @var string - The database table used by the model.
     */
    protected $table = 'deals';

    /**
     * @var array - The attributes that should be casted to native types.
     */
    protected $casts = [
        '_id' => 'integer',
        'vendors__id' => 'integer',
        'contacts__id' => 'integer',
        'pipeline_stages__id' => 'integer',
        'assigned_users__id' => 'integer',
        'value' => 'float',
        'position' => 'integer',
        'closed_at' => 'datetime',
    ];

    /**
     * @var array - The attributes that are mass assignable.
     */
    protected $fillable = [
        '_uid',
        'vendors__id',
        'contacts__id',
        'pipeline_stages__id',
        'assigned_users__id',
        'title',
        'value',
        'notes',
        'position',
        'closed_at',
    ];

    /**
     * Contact this deal belongs to.
     */
    public function contact()
    {
        return $this->belongsTo(ContactModel::class, 'contacts__id', '_id');
    }

    /**
     * Stage this deal currently sits in.
     */
    public function stage()
    {
        return $this->belongsTo(PipelineStageModel::class, 'pipeline_stages__id', '_id');
    }

    /**
     * Team member assigned to this deal, if any.
     */
    public function assignedUser()
    {
        return $this->belongsTo(AuthModel::class, 'assigned_users__id', '_id');
    }
}
