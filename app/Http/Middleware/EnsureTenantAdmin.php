<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Super admins and impersonators bypass the check
        if ($user && $user->is_super_admin) {
            return $next($request);
        }

        if (session()->has('super_admin_id')) {
            return $next($request);
        }

        $tenant = app()->bound('tenant') ? app('tenant') : null;

        if (!$tenant || !$user || (int) $user->tenant_id !== (int) $tenant->id) {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }
}
