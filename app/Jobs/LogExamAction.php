<?php

namespace App\Jobs;

use App\Models\ExamAuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogExamAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $attemptId,
        public string $action,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?array $payload = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        ExamAuditLog::create([
            'attempt_id' => $this->attemptId,
            'action'     => $this->action,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'payload'    => $this->payload,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
