<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use BelongsToTenant;

    /**
     * No updated_at column — only created_at (set via useCurrent in migration).
     */
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected $dates = ['created_at'];

    protected $fillable = [
        'tenant_id',
        'property_id',
        'staff_member_id',
        'visitor_name',
        'visitor_email',
        'visitor_phone',
        'appointment_type',
        'appointment_date',
        'appointment_time',
        'duration_minutes',
        'status',
        'notes',
        'source',
        'visitor_ip',
        'confirmation_token',
        'token_expires',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'token_expires'    => 'datetime',
        'created_at'       => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
