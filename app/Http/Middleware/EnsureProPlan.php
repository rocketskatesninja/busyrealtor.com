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
     * Pass the feature label as a middleware parameter:
     *   Route::middleware('plan.pro:Staff management')->group(...)
     */
    public function handle(Request $request, Closure $next, string $feature = 'This feature'): Response
    {
        $tenant = app()->bound('tenant') ? app('tenant') : null;

        if ($tenant && $tenant->isPro()) {
            return $next($request);
        }

        $message = "{$feature} is a Pro plan feature. Upgrade to access it.";

        if ($request->expectsJson()) {
            return response()->json(['error' => $message], 403);
        }

        $account = $tenant?->slug ?? $request->route('account');
        return redirect()->route('tenant.admin.billing', ['account' => $account])
            ->with('error', $message);
    }
}
