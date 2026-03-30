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

        $query = Tenant::where('slug', $slug);
        if (!$isImpersonating) {
            $query->where('is_active', true);
        }
        $tenant = $query->first();

        if (!$tenant) {
            abort(404);
        }

        app()->instance('tenant', $tenant);

        return $next($request);
    }
}
