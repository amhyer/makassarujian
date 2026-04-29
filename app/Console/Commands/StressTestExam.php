<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attempt;
use App\Models\User;
use App\Models\Exam;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use App\Jobs\SyncRedisAnswersToDatabase;

class StressTestExam extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'exam:stress-test {count=100} {--exam-id=}';

    /**
     * The console command description.
     */
    protected $description = 'Simulate high concurrency exam activity (Autosave & Counters)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = $this->argument('count');
        $examId = $this->option('exam-id') ?? Exam::first()?->id;

        if (!$examId) {
            $this->error('No exam found. Please seed data first.');
            return;
        }

        $this->info("Starting stress test for {$count} simulated students...");

        $bar = $this->output->createProgressBar($count);
        $redis = Redis::connection();
        $startTime = microtime(true);

        // 1. Simulate "Start Exam" (Counters & State)
        for ($i = 0; $i < $count; $i++) {
            $userId = "simulated_user_{$i}";
            $attemptId = "simulated_attempt_{$i}";

            // Simulate what AttemptObserver + Controller does
            $redis->incr("exam:{$examId}:total_participants");
            $redis->incr("exam:{$examId}:active_users");
            
            $redis->hset("attempt:{$attemptId}", [
                'exam_id' => $examId,
                'user_id' => $userId,
                'status' => 'ongoing',
                'expires_at' => now()->addMinutes(60)->timestamp
            ]);

            // 2. Simulate "Save Answer" (Hash + Dirty Set)
            for ($q = 1; $q <= 5; $q++) {
                $redis->hset("attempt:{$attemptId}:answers", "q{$q}", "option_a");
                $redis->sadd("attempts:dirty", $attemptId);
            }

            $bar->advance();
        }

        $bar->finish();
        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $this->newLine(2);
        $this->info("Test completed in " . round($duration, 2) . " seconds.");
        $this->info("Throughput: " . round($count / $duration, 2) . " students/sec.");
        $this->info("Total Redis ops: " . ($count * 8)); // 2 incrs + 1 hset + 5 hset answers

        $this->newLine();
        $this->warn("--- System State ---");
        $this->line("Active Users in Redis: " . $redis->get("exam:{$examId}:active_users"));
        $this->line("Dirty Attempts to Sync: " . $redis->scard("attempts:dirty"));

        if ($this->confirm('Run background sync job now?')) {
            $this->info("Running SyncRedisAnswersToDatabase...");
            $syncStart = microtime(true);
            SyncRedisAnswersToDatabase::dispatchSync();
            $syncEnd = microtime(true);
            $this->info("Sync completed in " . round($syncEnd - $syncStart, 2) . "s.");
        }
    }
}
