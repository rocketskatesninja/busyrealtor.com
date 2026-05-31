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
            'plan.pro'             => \App\Http\Middleware\EnsureProPlan::class,
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

        // 419 (Page Expired / CSRF mismatch) handler.
        //
        // Important: we cannot type-hint Illuminate\Session\TokenMismatchException
        // here because Laravel's Handler::render() wraps it into a Symfony
        // HttpException(419) inside prepareException() BEFORE our custom
        // render callbacks are checked. So we catch HttpException, look
        // for status 419, and return null for everything else (which lets
        // the framework's default 404/403/500/etc. handlers run normally).
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null; // not a CSRF mismatch — let default rendering handle it
            }

            // Authenticated user with a stale form (browser-back, long-idle
            // tab, etc.) — bounce them somewhere useful inside the app.
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

            // Common case: session timed out, user clicks logout (or any
            // POST with stale CSRF token). Send them to the login screen
            // with a friendly status message — never the raw 419 page.
            return redirect()->route('login')->with('status', 'Your session expired. Please sign in again.');
        });
    })->create();
