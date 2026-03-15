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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->guest(route('login'))->with('status', 'Your session has expired. Please sign in again.');
        });

        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            return redirect()->route('login')->with('status', 'Your session expired. Please sign in again.');
        });
    })->create();
