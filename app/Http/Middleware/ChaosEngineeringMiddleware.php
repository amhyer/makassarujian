<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ChaosEngineeringMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ── PRODUCTION GUARD ───────────────────────────────────────────────
        // Never allow Chaos Engineering to run in production, even if ENV says so.
        if (!app()->environment('local', 'testing', 'staging')) {
            abort(403, 'Chaos mode is strictly forbidden in production.');
        }

        // Only active in testing environment OR if explicitly enabled via env
        if (app()->environment('testing', 'local') || env('CHAOS_ENABLED', false)) {
            if ($request->header('X-Inject-Redis-Down') === 'true') {
                // Force Redis connection to fail by pointing to an invalid port
                config(['database.redis.client' => 'predis']);
                config(['database.redis.default.host' => '127.0.0.1']);
                config(['database.redis.default.port' => 65535]); // Invalid port
                
                // Purge existing connection to force reconnection attempt
                \Illuminate\Support\Facades\Redis::purge('default');
            }
        }

        return $next($request);
    }
}
