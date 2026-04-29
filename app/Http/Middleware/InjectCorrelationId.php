<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use OpenTelemetry\API\Trace\Span;

class InjectCorrelationId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Try to get from client header
        $correlationId = $request->header('X-Correlation-ID');

        // 2. Try to get OpenTelemetry Trace ID
        if (!$correlationId) {
            $spanContext = Span::getCurrent()->getContext();
            if ($spanContext->isValid()) {
                $correlationId = $spanContext->getTraceId();
            }
        }

        // 3. Fallback to UUID
        if (!$correlationId) {
            $correlationId = (string) Str::uuid();
        }

        // Add to Laravel 11 Context (automatically injected into logs & queues)
        Context::add('correlation_id', $correlationId);

        // Process Response
        $response = $next($request);

        // Add to Response Header
        if (method_exists($response, 'header')) {
            $response->header('X-Correlation-ID', $correlationId);
        }

        return $response;
    }
}
