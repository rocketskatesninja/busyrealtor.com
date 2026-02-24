<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'property_id',
        'sender_name',
        'sender_email',
        'sender_phone',
        'message',
        'source',
        'status',
        'is_read',
        'is_starred',
        'notes',
    ];

    protected $casts = [
        'is_read'    => 'boolean',
        'is_starred' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
