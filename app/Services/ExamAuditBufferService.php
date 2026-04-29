<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

/**
 * ExamAuditBufferService
 *
 * O(1) push to a per-attempt Redis list.
 * The list is later flushed to DB by FlushAttemptAuditBuffer job.
 *
 * Key schema: audit:buffer:{attempt_id}
 * TTL: 90 minutes (to auto-expire orphaned buffers from crashed sessions)
 */
class ExamAuditBufferService
{
    private const TTL_SECONDS = 5400; // 90 minutes

    public function push(string $attemptId, string $action, string $ip, string $userAgent, array $payload = []): void
    {
        $key = "audit:buffer:{$attemptId}";

        $logData = [
            'attempt_id' => $attemptId,
            'action'     => $action,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'payload'    => json_encode($payload),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];

        Redis::rpush($key, json_encode($logData));

        // Refresh TTL on every push to ensure buffer lives as long as the exam session
        Redis::expire($key, self::TTL_SECONDS);
    }
}
