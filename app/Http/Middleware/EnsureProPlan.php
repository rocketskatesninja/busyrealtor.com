<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProPlan
{
    /**
     * Block the request if the current tenant isn't on the Pro plan.
     *
     * - JSON requests get a 403 with `{ "error": "<message>" }`.
     * - Browser requests redirect to the tenant's billing page with a flash
     *   `error` describing the feature.
     *
     * The route may set the feature label via the `pro_feature` defaults:
     *   Route::get(...)->defaults('pro_feature', 'Staff management');
     * Falls back to a generic message when omitted.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app()->bound('tenant') ? app('tenant') : null;

        if ($tenant && $tenant->isPro()) {
            return $next($request);
        }

        $feature = $request->route()?->defaults['pro_feature'] ?? 'This feature';
        $message = "{$feature} is a Pro plan feature. Upgrade to access it.";

        if ($request->expectsJson()) {
            return response()->json(['error' => $message], 403);
        }

        $account = $tenant?->slug ?? $request->route('account');
        return redirect()->route('tenant.admin.billing', ['account' => $account])
            ->with('error', $message);
    }
}
