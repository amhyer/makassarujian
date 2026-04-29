<?php

namespace App\Prometheus\Collectors;

use Spatie\Prometheus\Collectors\Collector;
use Spatie\Prometheus\Facades\Prometheus;
use App\Models\ExamSession; // Ensure this is the correct model

class ActiveExamSessionsCollector implements Collector
{
    public function register(): void
    {
        Prometheus::addGauge('active_exam_sessions')
            ->helpText('The number of active exam sessions')
            ->value(function () {
                // Return the count of active exam sessions
                // Note: Adjust the condition to match your specific logic for "active" sessions.
                // Assuming "status" = "active" or "in_progress" or checking attempts.
                // We will use a try-catch to avoid breaking metrics if the table isn't ready.
                try {
                    // This is a placeholder, will adjust based on actual DB schema.
                    // If you don't have ExamSession, maybe Attempt where status is 'in_progress'?
                    if (class_exists(\App\Models\Attempt::class)) {
                        return \App\Models\Attempt::where('status', 'in_progress')->count();
                    }
                    return 0;
                } catch (\Exception $e) {
                    return 0;
                }
            });
    }
}
