<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('account');

        if (empty($slug)) {
            abort(404);
        }

        $isImpersonating = session()->has('super_admin_id');

        // Always look up by slug — never silently 404 on inactive tenants here.
        // The is_active gate is enforced below so we can let two groups
        // through even when the tenant is deactivated:
        //   1. Super admins impersonating (already supported)
        //   2. The tenant's own users — so they can reach /admin/billing
        //      and reactivate by subscribing. Without this, an expired
        //      trial = blank 404 with no path forward.
        $tenant = Tenant::where('slug', $slug)->first();

        if (!$tenant) {
            abort(404);
        }

        $user           = auth()->user();
        $userOwnsTenant = $user && (int) $user->tenant_id === (int) $tenant->id;

        // Owner bypass only applies to /admin/* routes — public pages
        // of an inactive tenant stay 404 for everyone (including the
        // owner) so the public site visibly goes dark.
        $isAdminRoute = str_contains($request->path(), '/admin');

        if (!$tenant->is_active && !$isImpersonating && !($userOwnsTenant && $isAdminRoute)) {
            abort(404);
        }

        app()->instance('tenant', $tenant);

        return $next($request);
    }
}
