<?php

namespace App\Prometheus\Collectors;

use Spatie\Prometheus\Collectors\Collector;
use Spatie\Prometheus\Facades\Prometheus;
use Laravel\Horizon\Contracts\MetricsRepository;
use Illuminate\Support\Facades\Queue;

class QueueSizeCollector implements Collector
{
    public function register(): void
    {
        Prometheus::addGauge('queue_size')
            ->helpText('Total number of jobs in the queue')
            ->value(function () {
                try {
                    // Try to get total queue size
                    return Queue::size();
                } catch (\Exception $e) {
                    return 0;
                }
            });
            
        Prometheus::addGauge('queue_throughput')
            ->helpText('Total jobs processed per minute')
            ->value(function () {
                try {
                    if (app()->bound(MetricsRepository::class)) {
                        $metrics = app(MetricsRepository::class);
                        return $metrics->jobsProcessedPerMinute();
                    }
                    return 0;
                } catch (\Exception $e) {
                    return 0;
                }
            });
    }
}
