<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSiteLocked
{
    public function handle(Request $request, Closure $next): Response
    {
        // Super admins always get through
        if (Auth::check() && Auth::user()->is_super_admin) {
            return $next($request);
        }

        $settings = SystemSetting::current();

        if ($settings->site_locked) {
            return response()->view('tenant.locked', [
                'message' => $settings->lock_message,
            ], 503);
        }

        return $next($request);
    }
}
