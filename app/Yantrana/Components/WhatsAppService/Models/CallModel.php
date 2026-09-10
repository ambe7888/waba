<?php
/**
* CallModel.php - Model file
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\WhatsAppService\Models;

use App\Yantrana\Base\BaseModel;

class CallModel extends BaseModel
{
    /**
     * @var string - The database table used by the model.
     */
    protected $table = 'calls';

    /**
     * @var array - The attributes that should be casted to native types.
     */
    protected $casts = [
        '_id' => 'integer',
        'vendors__id' => 'integer',
        'contacts__id' => 'integer',
        'duration' => 'integer',
        'initiated_by_users__id' => 'integer',
    ];

    /**
     * @var array - The attributes that are mass assignable.
     */
    protected $fillable = [
        '_uid',
        'vendors__id',
        'contacts__id',
        'type',
        'direction',
        'status',
        'duration',
        'call_id',
        'initiated_by_users__id',
    ];
}
