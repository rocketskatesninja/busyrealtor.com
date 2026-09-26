<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyView extends Model
{
    use BelongsToTenant;

    /*
     * Deliberately NOT using InvalidatesDashboardCache, unlike Property, Message and
     * Appointment.
     *
     * A row is written here on every public property pageview, so flushing the tenant's
     * dashboard cache from this model meant a single visitor browsing listings invalidated
     * it repeatedly and the 5-minute TTL never got to do anything: the next dashboard load
     * re-ran every aggregate. The three models that do invalidate are the ones an admin
     * changes deliberately and expects to see reflected at once. A view counter that trails
     * real traffic by up to five minutes is not worth re-running twenty aggregates for.
     */

    public $timestamps = false;

    protected $fillable = [
        'property_id',
        'tenant_id',
        'ip_address',
        'user_agent',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
