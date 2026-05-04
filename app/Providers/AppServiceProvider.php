<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Listeners\RealtimeEventDebugSubscriber;
use Illuminate\Support\Facades\Event;
use App\Events\Tenant\TenantCreated;
use App\Events\Tenant\TenantActivated;
use App\Events\Tenant\TenantExpired;
use App\Events\Tenant\ImpersonationStarted;
use App\Events\Tenant\ImpersonationStopped;

use App\Events\Billing\InvoiceCreated;
use App\Events\Billing\InvoiceDueSoon;
use App\Events\Billing\InvoiceOverdue;
use App\Events\Billing\TrialExpiring;
use App\Events\Billing\SubscriptionExpired;
use App\Listeners\Billing\BillingNotificationListener;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->configureLocalRedisFallback();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\Attempt::observe(\App\Observers\AttemptObserver::class);

        // ─── Rate Limiting (Laravel 11) ──────────────────────────────────────
        \Illuminate\Support\Facades\RateLimiter::for('exam-autosave', function ($request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(12)->by($request->user()?->id ?: $request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('exam-api', function ($request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Register Billing Events
        Event::listen(InvoiceCreated::class, BillingNotificationListener::class);
        Event::listen(InvoiceDueSoon::class, BillingNotificationListener::class);
        Event::listen(InvoiceOverdue::class, BillingNotificationListener::class);
        Event::listen(TrialExpiring::class, BillingNotificationListener::class);
        Event::listen(SubscriptionExpired::class, BillingNotificationListener::class);
        
        // Register Horizon Backpressure Monitor
        Event::listen(\Laravel\Horizon\Events\LongWaitDetected::class, \App\Listeners\MonitorQueueDelay::class);

        // Register Prometheus Collectors
        \Spatie\Prometheus\Facades\Prometheus::registerCollectorClasses([
            \App\Prometheus\Collectors\ActiveExamSessionsCollector::class,
            \App\Prometheus\Collectors\QueueDelayCollector::class,
            \App\Prometheus\Collectors\QueueSizeCollector::class,
            \App\Prometheus\Collectors\RedisSyncCollector::class,       // Legacy — sync_delay_seconds, redis_dirty_count
            \App\Prometheus\Collectors\RedisSyncLagCollector::class,    // NEW — exam_attempt_sync_lag_seconds, exam_attempts_dirty_total
        ]);
    }

    /**
     * Prevent local development from crashing when Redis is not running.
     */
    protected function configureLocalRedisFallback(): void
    {
        if (!$this->app->environment('local')) {
            return;
        }

        $cacheDriver = (string) config('cache.default', 'database');
        $sessionDriver = (string) config('session.driver', 'database');
        $queueDriver = (string) config('queue.default', 'database');

        $needsRedis = in_array($cacheDriver, ['redis'], true)
            || in_array($sessionDriver, ['redis'], true)
            || in_array($queueDriver, ['redis'], true);

        if (!$needsRedis) {
            return;
        }

        $host = (string) config('database.redis.default.host', '127.0.0.1');
        $port = (int) config('database.redis.default.port', 6379);

        $socket = @fsockopen($host, $port, $errno, $errstr, 0.25);
        if (is_resource($socket)) {
            fclose($socket);
            return;
        }

        config([
            'cache.default' => 'database',
            'cache.limiter' => 'database',
            'session.driver' => 'database',
            'session.store' => null,
            'queue.default' => 'database',
        ]);

        logger()->warning('Redis unavailable in local env, switched to database fallback.', [
            'redis_host' => $host,
            'redis_port' => $port,
            'errno' => $errno ?? null,
            'error' => $errstr ?? null,
        ]);
    }
}
