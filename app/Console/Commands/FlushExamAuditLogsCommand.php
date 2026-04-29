<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use App\Models\ExamAuditLog;

#[Signature('exam:flush-audit-logs')]
#[Description('Flush exam audit logs from Redis batch list to database in bulk')]
class FlushExamAuditLogsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $batchSize = 5000;
        $batch = [];
        
        $this->info("Starting to flush exam audit logs from Redis...");

        while (true) {
            // Retrieve up to 1 log per iteration (using LPOP is fast)
            // If we want to optimize we could use a Lua script or pipelining
            $logJson = Redis::lpop('exam_audit_logs_batch');
            
            if (!$logJson) {
                break; // No more logs
            }

            $logData = json_decode($logJson, true);
            if ($logData) {
                $batch[] = $logData;
            }

            if (count($batch) >= $batchSize) {
                ExamAuditLog::insert($batch);
                $this->info("Inserted " . count($batch) . " logs.");
                $batch = [];
            }
        }

        // Insert remaining
        if (count($batch) > 0) {
            ExamAuditLog::insert($batch);
            $this->info("Inserted " . count($batch) . " logs.");
        }

        $this->info("Finished flushing exam audit logs.");
    }
}
