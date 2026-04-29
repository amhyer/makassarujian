<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\RateLimiter;

class SecurePrometheusMetrics
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. IP Whitelist logic specifically for CIDR (since default Spatie is exact match only)
        $allowedIps = array_filter(explode(',', env('PROMETHEUS_ALLOWED_IPS', '')));
        if (!empty($allowedIps)) {
            $ipAllowed = false;
            foreach ($allowedIps as $ip) {
                if (\Symfony\Component\HttpFoundation\IpUtils::checkIp($request->ip(), trim($ip))) {
                    $ipAllowed = true;
                    break;
                }
            }
            if (!$ipAllowed) {
                abort(403, 'Forbidden metrics access');
            }
        }

        // 2. Token Auth
        $expectedToken = env('PROMETHEUS_METRICS_TOKEN');
        if (!empty($expectedToken) && $request->bearerToken() !== $expectedToken) {
            abort(401, 'Unauthorized metrics access');
        }

        // 3. Rate Limiting (e.g., 30 req / minute per IP)
        // Prometheus normally scrapes every 15s (4 req/min). 30 gives plenty of headroom.
        $executed = RateLimiter::attempt(
            'prometheus-metrics-'.$request->ip(),
            30,
            function() {}
        );

        if (! $executed) {
            abort(429, 'Too Many Requests');
        }

        return $next($request);
    }
}
