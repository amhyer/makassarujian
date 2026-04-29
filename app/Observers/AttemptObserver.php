<?php

namespace App\Observers;

use App\Models\Attempt;
use Illuminate\Support\Facades\Redis;

class AttemptObserver
{
    protected $shards = 10;

    /**
     * Handle the Attempt "created" event.
     */
    public function created(Attempt $attempt): void
    {
        $redis = Redis::connection();
        $examId = $attempt->exam_id;
        $attemptId = $attempt->id;

        // --- IDEMPOTENCY: Zero Double-Counting ---
        $idempotencyKey = "event:{$attemptId}:created";
        if (!$redis->set($idempotencyKey, 1, 'NX', 'EX', 600)) {
            return; // Already processed
        }

        $shard = rand(1, $this->shards);

        // Check if metadata exists...
        $metaKey = "exam:{$examId}:shards_meta";
        if (!$redis->exists($metaKey)) {
            $redis->hset($metaKey, 'version', time());
            $redis->hset($metaKey, 'expected_shards', $this->shards);
            $redis->expire($metaKey, 86400);
        }

        $redis->incr("exam:{$examId}:total_participants:shard:{$shard}");
        
        if ($attempt->status === 'ongoing') {
            $redis->incr("exam:{$examId}:active_users:shard:{$shard}");
        } elseif ($attempt->status === 'completed') {
            $redis->incr("exam:{$examId}:completed_users:shard:{$shard}");
        }

        $redis->expire("exam:{$examId}:total_participants:shard:{$shard}", 86400);
    }

    /**
     * Handle the Attempt "updated" event.
     */
    public function updated(Attempt $attempt): void
    {
        if ($attempt->wasChanged('status')) {
            $redis = Redis::connection();
            $examId = $attempt->exam_id;
            $attemptId = $attempt->id;
            $oldStatus = $attempt->getOriginal('status');
            $newStatus = $attempt->status;

            // Only care about transition to 'completed' for sharding stats
            if ($oldStatus === 'ongoing' && $newStatus === 'completed') {
                
                // --- IDEMPOTENCY: Transition must be unique ---
                $idempotencyKey = "event:{$attemptId}:completed";
                if (!$redis->set($idempotencyKey, 1, 'NX', 'EX', 600)) {
                    return;
                }

                $shard = rand(1, $this->shards);
                $redis->decr("exam:{$examId}:active_users:shard:{$shard}");
                $redis->incr("exam:{$examId}:completed_users:shard:{$shard}");
                $redis->sadd("exams:pending_broadcast", $examId);
            } 
            // Handle rare case: admin reset back to ongoing
            elseif ($oldStatus === 'completed' && $newStatus === 'ongoing') {
                $idempotencyKey = "event:{$attemptId}:reset";
                if (!$redis->set($idempotencyKey, 1, 'NX', 'EX', 600)) {
                    return;
                }

                $shard = rand(1, $this->shards);
                $redis->decr("exam:{$examId}:completed_users:shard:{$shard}");
                $redis->incr("exam:{$examId}:active_users:shard:{$shard}");
            }
        }
    }

    /**
     * Handle the Attempt "deleted" event.
     */
    public function deleted(Attempt $attempt): void
    {
        $redis = Redis::connection();
        $examId = $attempt->exam_id;
        $attemptId = $attempt->id;

        // --- IDEMPOTENCY: Delete must be unique ---
        $idempotencyKey = "event:{$attemptId}:deleted";
        if (!$redis->set($idempotencyKey, 1, 'NX', 'EX', 600)) {
            return;
        }

        $shard = rand(1, $this->shards);
        $redis->decr("exam:{$examId}:total_participants:shard:{$shard}");
        
        if ($attempt->status === 'ongoing') {
            $redis->decr("exam:{$examId}:active_users:shard:{$shard}");
        } elseif ($attempt->status === 'completed') {
            $redis->decr("exam:{$examId}:completed_users:shard:{$shard}");
        }
    }
}
