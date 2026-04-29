<?php

namespace App\Prometheus\Collectors;

use Spatie\Prometheus\Collectors\Collector;
use Spatie\Prometheus\Facades\Prometheus;
use Illuminate\Support\Facades\Redis;

class RedisSyncCollector implements Collector
{
    public function register(): void
    {
        Prometheus::addGauge('redis_dirty_count')
            ->helpText('Total number of unsynced dirty exam attempts in Redis')
            ->value(function () {
                try {
                    return (float) Redis::connection()->scard('attempts:dirty');
                } catch (\Exception $e) {
                    return 0;
                }
            });
            
        Prometheus::addGauge('sync_delay_seconds')
            ->helpText('Delay in seconds since the last successful Redis-DB sync')
            ->value(function () {
                try {
                    $dirtyCount = Redis::connection()->scard('attempts:dirty');
                    if ($dirtyCount == 0) {
                        return 0; // No delay if nothing is dirty
                    }

                    $lastSync = Redis::connection()->get('last_redis_sync_time');
                    if (!$lastSync) {
                        return 0; // Unknown
                    }

                    return (float) max(0, now()->timestamp - (int) $lastSync);
                } catch (\Exception $e) {
                    return 0;
                }
            });
    }
}
