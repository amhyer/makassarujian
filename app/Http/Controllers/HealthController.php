<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    /**
     * Handle the health check request for Load Balancer.
     */
    public function __invoke(Request $request)
    {
        $status = [
            'status' => 'OK',
            'database' => 'Disconnected',
            'redis' => 'Disconnected',
            'queue' => 'Unknown',
        ];

        $statusCode = 200;

        // Check Database
        try {
            DB::connection()->getPdo();
            $status['database'] = 'Connected';
        } catch (\Exception $e) {
            $status['status'] = 'Error';
            $status['database'] = 'Disconnected';
            $statusCode = 503;
            Log::error('HealthCheck: Database connection failed.', ['error' => $e->getMessage()]);
        }

        // Check Redis & Queue
        try {
            $redis = Redis::connection();
            $redis->ping();
            $status['redis'] = 'Connected';
            
            // In a Redis-backed queue setup, if Redis is up, we consider the queue connection Active.
            // Horizon manages the actual workers.
            $status['queue'] = 'Active';
        } catch (\Exception $e) {
            $status['status'] = 'Error';
            $status['redis'] = 'Disconnected';
            $status['queue'] = 'Unreachable';
            $statusCode = 503;
            Log::error('HealthCheck: Redis connection failed.', ['error' => $e->getMessage()]);
        }

        return response()->json($status, $statusCode);
    }
}
