<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use App\Jobs\HealthCheckJob;
use OpenTelemetry\API\Trace\Span;

class SystemHealthController extends Controller
{
    /**
     * Validate full system health and observability tracing.
     */
    public function health()
    {
        // 1. Logs: Check if trace_id is injected
        Log::info('System health check initiated.');

        $status = 'ok';
        $services = [];

        // 2. Tracing Database
        try {
            DB::select('SELECT 1');
            $services['database'] = 'ok';
        } catch (\Exception $e) {
            $services['database'] = 'failed';
            $status = 'degraded';
            Log::error('Database health check failed: ' . $e->getMessage());
        }

        // 3. Tracing Redis
        try {
            Redis::ping();
            $services['redis'] = 'ok';
        } catch (\Exception $e) {
            $services['redis'] = 'failed';
            $status = 'degraded';
            Log::error('Redis health check failed: ' . $e->getMessage());
        }

        // 4. Tracing Queue (dispatch to background)
        try {
            HealthCheckJob::dispatch();
            $services['queue'] = 'job_dispatched';
        } catch (\Exception $e) {
            $services['queue'] = 'dispatch_failed';
            $status = 'degraded';
            Log::error('Queue health check failed: ' . $e->getMessage());
        }

        // Try to fetch the active OpenTelemetry Trace ID
        $traceId = null;
        if (class_exists(Span::class)) {
            $span = Span::getCurrent();
            if ($span && $span->getContext()->isValid()) {
                $traceId = $span->getContext()->getTraceId();
            }
        }

        return response()->json([
            'status' => $status,
            'observability' => [
                'tracing_active' => $traceId !== null,
                'trace_id' => $traceId ?? 'not_available',
            ],
            'services' => $services,
            'timestamp' => now()->toIso8601String()
        ], $status === 'ok' ? 200 : 503);
    }
}
