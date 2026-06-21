<?php

namespace Addons\WhatsJetDripCampaignAddon\Models;

use App\Yantrana\Base\BaseModel;

class DripCampaign extends BaseModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'addon_drip_campaigns';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'vendors__id',
        'title',
        'status',
    ];

    public function steps()
    {
        return $this->hasMany(DripStep::class, 'addon_drip_campaigns__id', '_id');
    }

    public function subscribers()
    {
        return $this->hasMany(DripSubscriber::class, 'addon_drip_campaigns__id', '_id');
    }
}
