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

        $tenant = app()->bound('tenant') ? app('tenant') : null;

        // An impersonation session is a bypass only for the tenant it names. Without the
        // tenant check a session left holding super_admin_id — one whose impersonating_
        // tenant_id had gone, so HandleImpersonation had nothing to pin it to — was an
        // admin session for every tenant on the platform.
        if (session()->has('super_admin_id')
            && $tenant
            && (int) session('impersonating_tenant_id') === (int) $tenant->id) {
            return $next($request);
        }

        if (!$tenant || !$user || (int) $user->tenant_id !== (int) $tenant->id) {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }
}
