<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class EnforceIdempotency
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $paramNames Pipe-separated parameter names to check (e.g., "order_id|id")
     * @param  string  $prefix Redis key prefix
     * @param  int  $ttl Time to live in seconds
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $paramNames, string $prefix = 'idempotent:', int $ttl = 120)
    {
        $paramValue = null;
        $params = explode('|', $paramNames);

        // Cari parameter pertama yang ada di request
        foreach ($params as $param) {
            if ($request->has($param)) {
                $paramValue = $request->input($param);
                break;
            }
        }

        if (empty($paramValue)) {
            // Jika tidak ada ID unik, pass through (akan ditangkap oleh validasi form)
            return $next($request);
        }

        $idempotencyKey = $prefix . $paramValue;

        // Atomic lock menggunakan SETNX
        $acquired = Redis::connection()->set($idempotencyKey, 1, 'NX', 'EX', $ttl);

        if (!$acquired) {
            Log::warning("Duplicate request blocked by Idempotency Middleware.", [
                'key' => $idempotencyKey,
                'url' => $request->fullUrl(),
                'ip' => $request->ip()
            ]);
            
            return response()->json([
                'status' => 'duplicate',
                'message' => 'Request sedang diproses atau sudah pernah berhasil. Harap tunggu.'
            ], 409); // 409 Conflict
        }

        return $next($request);
    }
}
