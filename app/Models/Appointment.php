<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\InvalidatesDashboardCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use BelongsToTenant;
    use InvalidatesDashboardCache;

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
        'google_calendar_event_id',
        // Fillable so a restore can put back the original timestamp. RestoreController
        // deliberately unsets updated_at and keeps created_at, but without this the value
        // was dropped and the row was stamped with the time of the restore instead.
        'created_at',
    ];

    protected $casts = [
        'appointment_date' => 'date',
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
