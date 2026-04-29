<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use App\Jobs\FlushAttemptAuditBuffer;

#[Signature('exam:flush-audit-buffers')]
#[Description('Dispatch flush jobs for all active per-attempt audit buffers (runs every 30s via scheduler)')]
class FlushExamAuditBuffersCommand extends Command
{
    /**
     * Execute the console command.
     *
     * Scans all audit:buffer:{attempt_id} keys in Redis and dispatches
     * ONE FlushAttemptAuditBuffer job per active attempt buffer.
     *
     * This results in:
     *   - 1 job per attempt (never 1 job per click)
     *   - Automatic DB persistence every ~30 seconds
     *   - Full audit trail without queue saturation
     */
    public function handle()
    {
        $cursor = '0';
        $dispatched = 0;

        do {
            [$cursor, $keys] = Redis::scan($cursor, 'MATCH', 'audit:buffer:*', 'COUNT', 200);

            foreach ($keys as $key) {
                // Extract attempt_id from key: "audit:buffer:{attempt_id}"
                $attemptId = str_replace('audit:buffer:', '', $key);

                // Only dispatch if buffer has entries
                $length = Redis::llen($key);
                if ($length > 0) {
                    FlushAttemptAuditBuffer::dispatch($attemptId);
                    $dispatched++;
                }
            }
        } while ($cursor !== '0');

        $this->info("Dispatched {$dispatched} audit flush job(s).");
    }
}
