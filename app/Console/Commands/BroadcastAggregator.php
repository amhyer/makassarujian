<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use App\Services\DashboardService;
use App\Events\Exam\BatchProgressUpdated;

class BroadcastAggregator extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'exam:broadcast-aggregator';

    /**
     * The console command description.
     */
    protected $description = 'Aggregates stats updates and broadcasts every 3 seconds to reduce WebSocket traffic';

    /**
     * Execute the console command.
     */
    public function handle(DashboardService $dashboardService)
    {
        $this->info("Broadcast Aggregator started (3s interval)...");
        $redis = Redis::connection();

        while (true) {
            $examIds = $redis->spop("exams:pending_broadcast", 50); // Get up to 50 exams

            if (!empty($examIds)) {
                foreach ($examIds as $examId) {
                    try {
                        // Use DashboardService to get the sharded sum (fast O(shards))
                        // We need a dummy tenant ID since DashboardService requires it, 
                        // but sharded counters are indexed by examId globally.
                        $stats = $dashboardService->getExamStats('system', $examId);

                        if ($stats['realtime_enabled'] ?? false) {
                            broadcast(new \App\Events\Exam\StatsAggregated($examId, $stats['stats']))->toOthers();
                        }
                    } catch (\Exception $e) {
                        $this->error("Error aggregating for exam {$examId}: " . $e->getMessage());
                    }
                }
            }

            sleep(3); // 3-second aggregation window
        }
    }
}
