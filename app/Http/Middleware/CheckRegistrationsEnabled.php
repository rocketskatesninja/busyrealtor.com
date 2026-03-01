<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRegistrationsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $settings = SystemSetting::get();

        if (! $settings->registrations_enabled) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Registrations are currently closed.'], 503);
            }

            return response()->view('auth.registrations-closed', [], 503);
        }

        return $next($request);
    }
}
