<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ChaosDelayJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $delayMs;

    /**
     * Create a new job instance.
     */
    public function __construct($delayMs)
    {
        $this->delayMs = $delayMs;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (env('ALLOW_CHAOS_MODE') !== true) {
            return;
        }

        Log::warning("ChaosDelayJob started. Blocking worker for {$this->delayMs}ms.");
        
        // Sleep for the specified milliseconds to hog the worker
        usleep($this->delayMs * 1000);
        
        Log::info("ChaosDelayJob completed.");
    }
}
