<?php
namespace App\Yantrana\Components\CampaignAudience\Models;

use App\Yantrana\Base\BaseModel;

class CampaignAudienceModel extends BaseModel
{
    /**
     * @var string - The database table used by the model.
     */
    protected $table = 'campaign_audiences';

    /**
     * @var array - The attributes that should be casted to native types.
     */
    protected $casts = [
        'contacts' => 'array',
        'groups' => 'array',
        'labels' => 'array',
    ];

    /**
     * @var array - The attributes that are mass assignable.
     */
    protected $fillable = [];
}
