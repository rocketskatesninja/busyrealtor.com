<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\InvalidatesDashboardCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyView extends Model
{
    use BelongsToTenant;
    use InvalidatesDashboardCache;

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
