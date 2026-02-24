<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardWidgetOrder extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $table = 'dashboard_widget_order';

    protected $fillable = [
        'tenant_id',
        'section',
        'widget_key',
        'sort_order',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
