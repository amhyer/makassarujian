<?php

namespace App\Console\Commands;

use App\Models\Attempt;
use App\Services\DistributedConsistencyGuard;
use App\Services\SafeModeAnswerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CleanZombieSessions extends Command
{
    protected $signature   = 'exam:clean-zombies {--dry-run : List zombies without marking them completed}';
    protected $description = 'Auto-complete expired exam sessions where the student closed the browser.';

    public function handle(DistributedConsistencyGuard $guard): int
    {
        $zombies = Attempt::where('status', 'ongoing')
            ->where('expires_at', '<', now())
            ->get();

        if ($zombies->isEmpty()) {
            $this->info('No zombie sessions found.');
            return self::SUCCESS;
        }

        $this->info("Found {$zombies->count()} zombie session(s).");

        if ($this->option('dry-run')) {
            $this->table(
                ['ID', 'User ID', 'Exam ID', 'Expired At'],
                $zombies->map(fn ($a) => [$a->id, $a->user_id, $a->exam_id, $a->expires_at])
            );
            return self::SUCCESS;
        }

        $resolved = 0;
        $safeModeService = app(SafeModeAnswerService::class);

        foreach ($zombies as $attempt) {
            // Skip if another process is already handling this attempt
            $lockKey = "zombie_cleanup:{$attempt->id}";
            if (!Cache::add($lockKey, 1, 60)) {
                continue; // Already being cleaned by another worker
            }

            try {
                $payload = $guard->sign("attempt:{$attempt->id}", [
                    'action' => 'zombie_cleanup',
                ]);

                $accepted = $guard->guardedWrite(
                    "attempt:{$attempt->id}",
                    $payload,
                    function ($data) use ($attempt, $safeModeService) {
                        // Flush any buffered answers
                        $safeModeService->flush($attempt->id);

                        // Mark attempt as completed
                        $attempt->update([
                            'status' => 'completed',
                            'completed_at' => $attempt->expires_at,
                        ]);
                        // Redis cleanup handled inside flush service
                    }
                );

                if ($accepted) {
                    // RESULT SNAPSHOT for zombie attempts
                    try {
                        $attempt->refresh();
                        app(\App\Services\ScoreCalculator::class)->calculateAndPersist($attempt);
                    } catch (\Throwable $e) {
                        Log::warning("Zombie score snapshot failed for attempt {$attempt->id}: " . $e->getMessage());
                    }

                    $resolved++;
                    Log::info("Zombie session auto-completed.", [
                        'attempt_id' => $attempt->id,
                        'user_id'    => $attempt->user_id,
                        'exam_id'    => $attempt->exam_id,
                        'expired_at' => $attempt->expires_at,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error("Failed to clean zombie attempt {$attempt->id}: " . $e->getMessage());
            } finally {
                Cache::forget($lockKey);
            }
        }

        $this->info("Resolved {$resolved} zombie session(s).");
        return self::SUCCESS;
    }
}
