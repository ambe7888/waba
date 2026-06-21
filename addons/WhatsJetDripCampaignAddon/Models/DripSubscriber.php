<?php

namespace Addons\WhatsJetDripCampaignAddon\Models;

use App\Yantrana\Base\BaseModel;

class DripSubscriber extends BaseModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'addon_drip_subscribers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'addon_drip_campaigns__id',
        'contacts__id',
        'start_date',
        'last_step_id',
        'status',
    ];

    public function campaign()
    {
        return $this->belongsTo(DripCampaign::class, 'addon_drip_campaigns__id', '_id');
    }

    public function contact()
    {
        return $this->belongsTo(\App\Yantrana\Components\Contact\Models\ContactModel::class, 'contacts__id', '_id');
    }
}
