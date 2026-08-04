<?php
/**
* ContactReminderModel.php - Model file
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Contact\Models;

use App\Yantrana\Base\BaseModel;

class ContactReminderModel extends BaseModel
{
    /**
     * @var string - The database table used by the model.
     */
    protected $table = 'contact_reminders';

    /**
     * @var array - The attributes that should be casted to native types.
     */
    protected $casts = [
        '_id' => 'integer',
        'vendors__id' => 'integer',
        'contacts__id' => 'integer',
        'users__id' => 'integer',
        'status' => 'integer',
        '__data' => 'array',
    ];

    /**
     * @var array - The attributes that are mass assignable.
     */
    protected $fillable = [
        '_uid',
        'vendors__id',
        'contacts__id',
        'users__id',
        'scheduled_at',
        'action_type',
        'title_note',
        'template_name',
        'template_language',
        'status',
        '__data',
    ];
}
