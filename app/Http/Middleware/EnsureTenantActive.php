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
