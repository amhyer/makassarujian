<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\AuditLog;
use App\Models\CheatLog;
use App\Models\Answer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ArchiveOldData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     * Enforces the Data Retention Policy.
     */
    public function handle(): void
    {
        Log::info('ArchiveOldData: Starting data retention enforcement...');

        // 1. Audit Logs: Archive > 30 days
        $this->archiveAuditLogs(30);

        // 2. Cheat Logs: Archive > 90 days
        $this->archiveCheatLogs(90);

        // 3. Attempt Answers: Delete > 180 days (assuming summaries/scores are permanent)
        $this->purgeOldAnswers(180);

        Log::info('ArchiveOldData: Data retention enforcement completed.');
    }

    protected function archiveAuditLogs(int $days)
    {
        $cutoff = now()->subDays($days);
        $logs = AuditLog::where('created_at', '<', $cutoff)->get();

        if ($logs->count() > 0) {
            $filename = 'archives/audit_logs_' . $cutoff->format('Y_m_d') . '.json';
            Storage::put($filename, $logs->toJson());
            
            AuditLog::where('created_at', '<', $cutoff)->delete();
            Log::info("Archived {$logs->count()} audit logs to {$filename}");
        }
    }

    protected function archiveCheatLogs(int $days)
    {
        $cutoff = now()->subDays($days);
        $logs = CheatLog::where('created_at', '<', $cutoff)->get();

        if ($logs->count() > 0) {
            $filename = 'archives/cheat_logs_' . $cutoff->format('Y_m_d') . '.json';
            Storage::put($filename, $logs->toJson());
            
            CheatLog::where('created_at', '<', $cutoff)->delete();
            Log::info("Archived {$logs->count()} cheat logs to {$filename}");
        }
    }

    protected function purgeOldAnswers(int $days)
    {
        $cutoff = now()->subDays($days);
        $count = Answer::where('created_at', '<', $cutoff)->delete();
        
        if ($count > 0) {
            Log::info("Purged {$count} old answers older than {$days} days.");
        }
    }
}
