<?php

use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureTenantActive;
use App\Http\Middleware\EnsureTenantAdmin;
use App\Http\Middleware\HandleImpersonation;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant'               => ResolveTenant::class,
            'super.admin'          => EnsureSuperAdmin::class,
            'tenant.active'        => EnsureTenantActive::class,
            'tenant.admin'         => EnsureTenantAdmin::class,
            'impersonate'          => HandleImpersonation::class,
            'registrations.enabled'=> \App\Http\Middleware\CheckRegistrationsEnabled::class,
            'site.lock'            => \App\Http\Middleware\CheckSiteLocked::class,
            'verified'             => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'no.cache'             => \App\Http\Middleware\NoCacheHeaders::class,
        ]);

        // Stripe webhooks POST from outside our session — exclude from CSRF.
        $middleware->validateCsrfTokens(except: [
            'stripe/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->guest(route('login'))->with('status', 'Your session has expired. Please sign in again.');
        });

        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            // If the user is still authenticated (the typical case when
            // they hit browser back to a stale form), keep them in the
            // app — bounce them to a sensible landing page instead of
            // /login. Only truly unauthenticated requests go to /login.
            if ($user = auth()->user()) {
                $msg = 'That form expired. Please try again.';

                if ($user->is_super_admin) {
                    return redirect()->route('super.dashboard')->with('status', $msg);
                }

                $tenant = $user->tenant_id ? \App\Models\Tenant::find($user->tenant_id) : null;
                if ($tenant) {
                    return redirect()
                        ->route('tenant.admin.dashboard', ['account' => $tenant->slug])
                        ->with('status', $msg);
                }

                // Authenticated but tenant lookup failed — fall through.
            }

            return redirect()->route('login')->with('status', 'Your session expired. Please sign in again.');
        });
    })->create();
