<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append([
            \App\Http\Middleware\InjectCorrelationId::class,
            \App\Http\Middleware\PrometheusRequestDurationMiddleware::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\LogImpersonationActivity::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'webhook/*',
        ]);
        $middleware->alias([
            'role'                => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'          => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission'  => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'subscription.active' => \App\Http\Middleware\EnsureSubscriptionActive::class,
            'prevent.impersonate' => \App\Http\Middleware\PreventImpersonatingCriticalAction::class,
            'idempotent'          => \App\Http\Middleware\EnforceIdempotency::class,
            'secure.debug'        => \App\Http\Middleware\SecureDebugEndpoints::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
