<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantActive
{
    public function handle(Request $request, Closure $next): Response
    {
        // Super admins bypass tenant activity checks
        if (auth()->check() && auth()->user()->is_super_admin) {
            return $next($request);
        }

        if (session()->has('super_admin_id')) {
            return $next($request);
        }

        // Billing route bypass — a deactivated tenant must still be able
        // to reach /admin/billing to subscribe and reactivate. Without
        // this, the redirect below would bounce back to itself.
        $routeName = $request->route()?->getName() ?? '';
        if (str_starts_with($routeName, 'tenant.admin.billing')) {
            return $next($request);
        }

        $tenant = app()->bound('tenant') ? app('tenant') : null;

        if (!$tenant || !$tenant->isActive()) {
            $account = $request->route('account') ?? ($tenant ? $tenant->slug : null);
            return redirect()
                ->to($account ? '/' . $account . '/admin/billing' : '/')
                ->with('error', 'Your account is not active. Please check your subscription.');
        }

        return $next($request);
    }
}
