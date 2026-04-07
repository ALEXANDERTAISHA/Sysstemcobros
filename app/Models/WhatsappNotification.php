<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappNotification extends Model
{
    protected $fillable = [
        'recipient_phone',
        'recipient_name',
        'message',
        'status',
        'related_type',
        'related_id',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
