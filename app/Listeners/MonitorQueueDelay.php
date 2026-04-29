<?php

namespace App\Listeners;

use Laravel\Horizon\Events\LongWaitDetected;
use Illuminate\Support\Facades\Log;

class MonitorQueueDelay
{
    /**
     * Handle the event.
     */
    public function handle(LongWaitDetected $event): void
    {
        Log::emergency("CRITICAL QUEUE DELAY: Connection [{$event->connection}] on queue [{$event->queue}] has a wait time of {$event->seconds} seconds!");
        
        // --- Integration Idea ---
        // Here you could send Slack/Discord alerts, SMS, or Telegram messages.
    }
}
