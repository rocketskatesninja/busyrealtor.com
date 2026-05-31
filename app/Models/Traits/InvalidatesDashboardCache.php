<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Cache;

/**
 * Forget the cached dashboard payload for the affected tenant whenever a
 * model that feeds the dashboard (Property, Message, Appointment,
 * PropertyView) is created, updated, or deleted. DashboardController
 * rebuilds and re-caches on the next page load.
 *
 * Key shape must match DashboardController::CACHE_KEY_PREFIX.
 */
trait InvalidatesDashboardCache
{
    public static function bootInvalidatesDashboardCache(): void
    {
        $forget = function ($model) {
            if (!empty($model->tenant_id)) {
                Cache::forget('dashboard:' . $model->tenant_id . ':v1');
            }
        };

        static::saved($forget);
        static::deleted($forget);
    }
}
