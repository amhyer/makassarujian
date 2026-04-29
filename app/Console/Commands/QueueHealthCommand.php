<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Laravel\Horizon\WaitTimeCalculator;
use Laravel\Horizon\Contracts\JobRepository;

class QueueHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor Horizon queue health, alert on backlog, and auto-scale workers.';

    /**
     * Execute the console command.
     */
    public function handle(WaitTimeCalculator $waitTimeCalculator, JobRepository $jobRepository)
    {
        $this->info('--- Horizon Queue Health Check ---');

        try {
            // 1. Get Metrics
            $pending = Queue::size('default');
            
            // Wait time in seconds for the 'default' queue on 'redis' connection
            $waitSeconds = $waitTimeCalculator->calculate('redis', 'default');
            
            // Failed jobs (counting all recent failed jobs)
            $failedCount = $jobRepository->countRecentlyFailed();

            // 2. Display Metrics
            $this->table(
                ['Metric', 'Value', 'Status'],
                [
                    [
                        'Wait Time (Seconds)',
                        $waitSeconds,
                        $waitSeconds > 5 ? '<error>HIGH</error>' : '<info>OK</info>'
                    ],
                    [
                        'Jobs Pending',
                        number_format($pending),
                        $pending > 10000 ? '<error>CRITICAL</error>' : ($pending > 1000 ? '<comment>WARNING</comment>' : '<info>OK</info>')
                    ],
                    [
                        'Jobs Failed (Recent)',
                        number_format($failedCount),
                        $failedCount > 0 ? '<comment>ATTENTION</comment>' : '<info>OK</info>'
                    ]
                ]
            );

            // 3. Alert & Auto-Action
            $needsScaling = false;
            $alertReasons = [];

            if ($waitSeconds > 5) {
                $needsScaling = true;
                $alertReasons[] = "Wait time is {$waitSeconds}s (> 5s)";
            }

            if ($pending > 10000) {
                $needsScaling = true;
                $alertReasons[] = "Pending jobs at {$pending} (> 10k)";
            }

            if ($needsScaling) {
                $reasonStr = implode(" AND ", $alertReasons);
                $this->error("⚠️ ALERT: Backlog detected! ({$reasonStr})");
                Log::warning("Queue Health Alert: {$reasonStr}");

                // 4. Auto-action: Delegate to Horizon / Orchestrator
                $this->warn("⚙️ AUTO-ACTION: Delegating to Managed Worker Autoscaler...");
                $this->info("✅ System relies on Horizon Auto-Balancing or Kubernetes HPA (KEDA) to scale workers dynamically.");
                Log::info("Queue Health: Backlog detected. Waiting for Horizon/Orchestrator to scale up managed workers.");
                
                return Command::FAILURE;
            }

            $this->info("✅ Queue is healthy.");
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to retrieve Horizon metrics: ' . $e->getMessage());
            Log::error("Queue Health Command Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
