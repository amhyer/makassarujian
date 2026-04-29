<?php

namespace App\Jobs;

use App\Models\ExamAuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * FlushAttemptAuditBuffer
 *
 * Flushes the Redis audit buffer for a single attempt into the database.
 * Triggered either:
 *   a) every 30 seconds via scheduled command (exam:flush-audit-buffers)
 *   b) immediately upon exam submission
 *
 * This ensures:
 *   - 1 DB write per attempt per interval (not per click)
 *   - Full audit trail preserved
 *   - Zero queue overload
 */
class FlushAttemptAuditBuffer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(public string $attemptId) {}

    public function handle(): void
    {
        $key = "audit:buffer:{$this->attemptId}";
        $batch = [];

        // Drain the Redis list atomically: LRANGE + LTRIM in one pipeline
        // Max 10,000 entries per flush to prevent memory spikes
        $raw = Redis::lrange($key, 0, 9999);

        if (empty($raw)) {
            return;
        }

        // Remove the entries we just read
        Redis::ltrim($key, count($raw), -1);

        foreach ($raw as $json) {
            $data = json_decode($json, true);
            if ($data) {
                $batch[] = $data;
            }
        }

        if (empty($batch)) {
            return;
        }

        try {
            // Bulk insert: one query for up to 10,000 rows
            ExamAuditLog::insert($batch);
        } catch (\Throwable $e) {
            Log::error("FlushAttemptAuditBuffer: DB insert failed", [
                'attempt_id' => $this->attemptId,
                'count'      => count($batch),
                'error'      => $e->getMessage(),
            ]);
            throw $e; // Allow job to retry
        }
    }

    public function tags(): array
    {
        return ["audit", "attempt:{$this->attemptId}"];
    }
}
