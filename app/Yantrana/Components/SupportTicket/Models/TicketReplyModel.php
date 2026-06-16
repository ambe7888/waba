<?php

namespace App\Yantrana\Components\SupportTicket\Models;

use App\Yantrana\Base\BaseModel;
use App\Yantrana\Components\Auth\Models\AuthModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketReplyModel extends BaseModel
{
    protected $table = 'ticket_replies';

    protected $casts = [
        '__data' => 'array',
    ];

    protected $fillable = [
        'tickets__id',
        'users__id',
        'message',
        '__data'
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(TicketModel::class, 'tickets__id', '_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(AuthModel::class, 'users__id', '_id');
    }
}
