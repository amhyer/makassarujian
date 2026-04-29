<?php

namespace App\Services;

use App\Models\Attempt;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    /**
     * Get aggregate statistics for an exam dashboard.
     * Strategy: Fast Redis cache with 15s TTL.
     */
    public function getExamStats(string $tenantId, string $examId)
    {
        try {
            $redis = \Illuminate\Support\Facades\Redis::connection();
            $shards = 10;
            
            // --- PROTECTION: Epoch Validation ---
            if (!$redis->exists("exam:{$examId}:shards_meta")) {
                return $this->resyncCountersFromDb($tenantId, $examId);
            }

            $total = 0;
            $completed = 0;
            $active = 0;
            $found = false;

            for ($i = 1; $i <= $shards; $i++) {
                $t = $redis->get("exam:{$examId}:total_participants:shard:{$i}");
                $c = $redis->get("exam:{$examId}:completed_users:shard:{$i}");
                $a = $redis->get("exam:{$examId}:active_users:shard:{$i}");

                // --- CRITICAL: Silent Corruption Protection ---
                // If any shard is missing (evicted/expired), the data is inconsistent.
                if ($t === null || $c === null || $a === null) {
                    \Illuminate\Support\Facades\Log::warning("Shard inconsistency detected for Exam {$examId} at shard {$i}. Triggering auto-healing.");
                    $found = false;
                    break;
                }

                $total += (int) $t;
                $completed += (int) $c;
                $active += (int) $a;
                $found = true;
            }

            if ($found) {
                return [
                    'stats' => [
                        'total_participants' => $total,
                        'completed' => $completed,
                        'ongoing' => $active,
                    ],
                    'last_updated' => now()->toDateTimeString(),
                    'source' => 'redis_sharded_precomputed',
                    'realtime_enabled' => true
                ];
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Redis unavailable: " . $e->getMessage());
        }

        $dbStats = $this->resyncCountersFromDb($tenantId, $examId);
        $dbStats['realtime_enabled'] = false; // Graceful degradation
        $dbStats['message'] = 'Sistem real-time dinonaktifkan sementara karena beban tinggi.';
        
        return $dbStats;
    }

    /**
     * Resync counters from database to Redis.
     * Protected by Circuit Breaker to prevent DB overload during Redis outage.
     */
    public function resyncCountersFromDb(string $tenantId, string $examId)
    {
        $cacheKey = "dashboard:{$tenantId}:exam:{$examId}";
        $breakerKey = "breaker:dashboard:{$examId}";

        // Check if circuit is open (broken)
        if (Cache::has($breakerKey)) {
            \Illuminate\Support\Facades\Log::error("Circuit Breaker OPEN for Exam {$examId}. Avoiding DB hit.");
            return [
                'total_participants' => 0,
                'completed' => 0,
                'ongoing' => 0,
                'last_updated' => now()->toDateTimeString(),
                'source' => 'circuit_breaker_open'
            ];
        }

        return Cache::store('redis')->remember($cacheKey, 15, function () use ($cacheKey, $breakerKey, $tenantId, $examId) {
            return Cache::lock("lock:{$cacheKey}", 5)->block(3, function () use ($tenantId, $examId, $breakerKey) {
                try {
                    $stats = Attempt::selectRaw("
                        COUNT(*) as total_participants,
                        COUNT(*) FILTER (WHERE status = 'completed') as completed,
                        COUNT(*) FILTER (WHERE status = 'ongoing') as ongoing
                    ")
                    ->where('tenant_id', $tenantId)
                    ->where('exam_id', $examId)
                    ->first();

                    $data = [
                        'total_participants' => (int) $stats->total_participants,
                        'completed' => (int) $stats->completed,
                        'ongoing' => (int) $stats->ongoing,
                        'last_updated' => now()->toDateTimeString(),
                        'source' => 'db_resync'
                    ];

                    // Update Redis Counters: Rebuild shards (Put all into Shard 1, reset 2-10)
                    $redis = \Illuminate\Support\Facades\Redis::connection();
                    $shards = 10;
                    
                    // Mark metadata
                    $redis->hset("exam:{$examId}:shards_meta", 'expected_shards', $shards);
                    
                    for ($i = 1; $i <= $shards; $i++) {
                        if ($i === 1) {
                            $redis->set("exam:{$examId}:total_participants:shard:{$i}", $data['total_participants'], 'EX', 86400);
                            $redis->set("exam:{$examId}:completed_users:shard:{$i}", $data['completed'], 'EX', 86400);
                            $redis->set("exam:{$examId}:active_users:shard:{$i}", $data['ongoing'], 'EX', 86400);
                        } else {
                            $redis->set("exam:{$examId}:total_participants:shard:{$i}", 0, 'EX', 86400);
                            $redis->set("exam:{$examId}:completed_users:shard:{$i}", 0, 'EX', 86400);
                            $redis->set("exam:{$examId}:active_users:shard:{$i}", 0, 'EX', 86400);
                        }
                    }

                    return $data;
                } catch (\Exception $e) {
                    // Open the circuit for 30 seconds to protect DB
                    Cache::put($breakerKey, true, 30);
                    throw $e;
                }
            });
        });
    }

    /**
     * Clear dashboard cache for a specific exam.
     */
    public function invalidateCache(string $tenantId, string $examId)
    {
        Cache::store('redis')->forget("dashboard:{$tenantId}:exam:{$examId}");
    }
}
