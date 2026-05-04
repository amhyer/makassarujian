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
            \App\Http\Middleware\ChaosEngineeringMiddleware::class,
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
            'exam.session'        => \App\Http\Middleware\CheckExamSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // --- Exam Domain Exceptions → Proper HTTP JSON Responses ---
        // AlreadySubmittedException: 409 Conflict — student tried to submit twice
        $exceptions->render(function (\App\Exceptions\AlreadySubmittedException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error'   => 'already_submitted',
                ], 409);
            }
        });

        // ExamExpiredException: 403 Forbidden — submission after time window closed
        $exceptions->render(function (\App\Exceptions\ExamExpiredException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error'   => 'exam_expired',
                ], 403);
            }
        });

        // NotAParticipantException: 403 Forbidden — user not registered for this exam
        $exceptions->render(function (\App\Exceptions\NotAParticipantException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error'   => 'not_registered',
                ], 403);
            }
        });

        // ExamNotAvailableException: 403 Forbidden — exam is draft or outside schedule
        $exceptions->render(function (\App\Exceptions\ExamNotAvailableException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error'   => 'exam_not_available',
                ], 403);
            }
        });

        // AlreadyAttemptedException: 409 Conflict — user already completed this exam
        $exceptions->render(function (\App\Exceptions\AlreadyAttemptedException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error'   => 'already_attempted',
                ], 409);
            }
        });
    })->create();
