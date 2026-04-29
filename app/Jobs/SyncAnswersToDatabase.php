<?php

namespace App\Jobs;

use App\Services\SafeModeAnswerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncAnswersToDatabase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 120; // 2 menit untuk flush banyak data

    /**
     * Process the job.
     */
    public function handle(SafeModeAnswerService $syncService): void
    {
        if (!config('queue.default') || config('queue.default') === 'sync') {
            Log::warning("SyncAnswersToDatabase skipped: queue driver is sync");
            return;
        }

        try {
            $result = $syncService->flushAllDirty(limit: 100);

            Log::info("SyncAnswersToDatabase completed", [
                'processed' => $result['processed'],
                'failed' => $result['failed'],
                'total_synced' => $result['total_synced'],
            ]);

            // Jika safe mode dinonaktifkan dan tidak ada backlog, disable safe mode
            if ($result['processed'] > 0 && $result['failed'] === 0) {
                $metrics = $syncService->getMetrics();
                if (($metrics['dirty_attempts_count'] ?? 0) < SafeModeAnswerService::SAFE_MODE_THRESHOLD) {
                    SafeModeAnswerService::disable();
                }
            }
        } catch (\Throwable $e) {
            Log::error("SyncAnswersToDatabase failed: " . $e->getMessage(), [
                'exception' => $e,
            ]);
            throw $e; // Re-throw untuk retry
        }
    }

    /**
     * Determine if the job should be retried.
     */
    public function shouldRetryForExceptions(array $failedJobExceptionProperties): bool
    {
        // Retry hanya untuk network/timeout errors, bukan validation errors
        return true;
    }

    /**
     * Get the display name for the job.
     */
    public function displayName(): string
    {
        return 'Sync Exam Answers to Database';
    }
}
