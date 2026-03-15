<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailCampaign extends Model
{
    protected $fillable = [
        'subject',
        'body',
        'recipient_count',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
