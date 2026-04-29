<?php

namespace App\Jobs;

use App\Models\Attempt;
use App\Models\Answer;
use App\Models\Question;
use App\Events\Exam\BatchProgressUpdated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncRedisAnswersToDatabase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     * Strategy: HIGH PERFORMANCE DIRTY SYNC + BATCH BROADCAST.
     */
    public function handle(): void
    {
        $redis = Redis::connection();
        $batchSize = 200;
        
        $dirtyAttemptIds = $redis->spop("attempts:dirty", $batchSize);

        if (empty($dirtyAttemptIds)) {
            return;
        }

        $progressUpdates = []; // Grouped by exam_id

        foreach ($dirtyAttemptIds as $attemptId) {
            try {
                $answers = $redis->hgetall("attempt:{$attemptId}:answers");
                if (empty($answers)) continue;

                \Illuminate\Support\Facades\Cache::lock("flush:{$attemptId}", 30)->get(function () use ($attemptId, $answers, &$progressUpdates, $redis) {
                    DB::transaction(function () use ($attemptId, $answers, &$progressUpdates, $redis) {
                        $attempt = Attempt::find($attemptId);
                        if (!$attempt) return;

                        // Sync to Attempt table
                        $attempt->update([
                            'answers' => $answers,
                            'last_synced_at' => now()
                        ]);

                        // Calculate progress for batch broadcast
                        $totalQuestions = $redis->get("exam:{$attempt->exam_id}:total_questions") ?: 1;
                        $progress = round((count($answers) / $totalQuestions) * 100);
                        
                        $progressUpdates[$attempt->exam_id][] = [
                            'user_id' => $attempt->user_id,
                            'progress' => $progress
                        ];
                    });
                });
            } catch (\Exception $e) {
                Log::error("Failed to sync attempt {$attemptId}: " . $e->getMessage());
                $redis->sadd("attempts:dirty", $attemptId);
            }
        }

        // --- BATCH BROADCAST: One event per Exam instead of one per student ---
        foreach ($progressUpdates as $examId => $updates) {
            broadcast(new \App\Events\Exam\BatchProgressUpdated($examId, $updates))->toOthers();
        }

        // Record successful sync time for monitoring
        $redis->set('last_redis_sync_time', now()->timestamp);
    }
}
