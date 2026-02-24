<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleImpersonation
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('impersonating_tenant_id')) {
            $tenant = Tenant::find(session('impersonating_tenant_id'));
            if ($tenant) {
                app()->instance('tenant', $tenant);
            }
        }

        return $next($request);
    }
}
