<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\RateLimiter;

class SecureDebugEndpoints
{
    /**
     * Handle an incoming request to debug endpoints.
     * 
     * Security layers:
     * 1. Role check: Super Admin only
     * 2. IP Whitelist: Only allowed IPs (supports CIDR notation)
     * 3. Rate Limiting: Prevent abuse
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Role Check: Must be Super Admin
        if (!$request->user() || !$request->user()->hasRole('Super Admin')) {
            abort(403, 'Debug endpoints are restricted to Super Admin only');
        }

        // 2. IP Whitelist (supports CIDR notation like 192.168.1.0/24)
        $allowedIps = array_filter(explode(',', env('DEBUG_ALLOWED_IPS', '')));
        
        if (!empty($allowedIps)) {
            $ipAllowed = false;
            $clientIp = $request->ip();
            
            foreach ($allowedIps as $allowedIp) {
                $allowedIp = trim($allowedIp);
                
                // Support CIDR notation using Symfony's IpUtils
                if (\Symfony\Component\HttpFoundation\IpUtils::checkIp($clientIp, $allowedIp)) {
                    $ipAllowed = true;
                    break;
                }
            }
            
            if (!$ipAllowed) {
                \Log::warning('Debug endpoint access denied', [
                    'ip' => $clientIp,
                    'user_id' => $request->user()->id,
                    'endpoint' => $request->path(),
                ]);
                
                abort(403, 'Your IP address is not whitelisted for debug access');
            }
        }

        // 3. Rate Limiting: 20 requests per minute per user
        // This prevents abuse even from authorized Super Admins
        $key = 'debug-endpoint:' . $request->user()->id;
        
        $executed = RateLimiter::attempt(
            $key,
            20, // max attempts
            function() {},
            60  // decay in seconds (1 minute)
        );

        if (!$executed) {
            abort(429, 'Too many debug requests. Please wait before trying again.');
        }

        return $next($request);
    }
}

