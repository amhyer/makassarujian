<?php

namespace App\Prometheus\Collectors;

use Spatie\Prometheus\Collectors\Collector;
use Spatie\Prometheus\Facades\Prometheus;
use Laravel\Horizon\Contracts\MetricsRepository;

class QueueDelayCollector implements Collector
{
    public function register(): void
    {
        Prometheus::addGauge('queue_delay_seconds')
            ->helpText('Maximum queue delay in seconds across all queues')
            ->value(function () {
                try {
                    // Try to get metrics from Horizon if it's available
                    if (app()->bound(MetricsRepository::class)) {
                        $metrics = app(MetricsRepository::class);
                        $delays = $metrics->measuredJobs(); 
                        // Wait, a better way is to use Horizon's wait times
                        $waitTimes = app(\Laravel\Horizon\Contracts\WaitTimeCalculator::class)->calculate();
                        if (is_array($waitTimes) && count($waitTimes) > 0) {
                            return max($waitTimes);
                        }
                    }
                    return 0;
                } catch (\Exception $e) {
                    return 0;
                }
            });
    }
}
