<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Prometheus\CollectorRegistry;

class PrometheusRequestDurationMiddleware
{
    protected CollectorRegistry $registry;

    public function __construct(CollectorRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = microtime(true) - $startTime;

        // Determine route. If 404, route might be null.
        $route = $request->route() ? $request->route()->uri() : 'unknown';
        $method = $request->method();
        $status = (string) $response->getStatusCode();

        try {
            $histogram = $this->registry->getOrRegisterHistogram(
                config('prometheus.default_namespace', 'app'),
                'request_duration_seconds',
                'HTTP request latency in seconds',
                ['route', 'method', 'status'],
                [0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0]
            );

            $histogram->observe($duration, [$route, $method, $status]);
        } catch (\Exception $e) {
            // Fail silently so metrics don't break the application
            \Illuminate\Support\Facades\Log::warning('Prometheus metrics failed to record', ['error' => $e->getMessage()]);
        }

        return $response;
    }
}
