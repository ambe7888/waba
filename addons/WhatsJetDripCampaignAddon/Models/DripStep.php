<?php

namespace Addons\WhatsJetDripCampaignAddon\Models;

use App\Yantrana\Base\BaseModel;

class DripStep extends BaseModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'addon_drip_steps';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'addon_drip_campaigns__id',
        'delay_value',
        'delay_type',
        'whatsapp_templates__id',
        'custom_message',
    ];

    public function campaign()
    {
        return $this->belongsTo(DripCampaign::class, 'addon_drip_campaigns__id', '_id');
    }

    public function template()
    {
        return $this->belongsTo(\App\Yantrana\Components\WhatsAppService\Models\WhatsAppTemplateModel::class, 'whatsapp_templates__id', '_id');
    }
}
