<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Redis;
use Illuminate\Events\Dispatcher;

class RealtimeEventDebugSubscriber
{
    /**
     * Increment the event counter in Redis.
     */
    private function incrementCounter($examId)
    {
        if (!$examId) return;
        
        $timestamp = time();
        $key = "debug:events:{$examId}:{$timestamp}";
        
        // Use a pipeline to increment and set expiration securely
        Redis::pipeline(function ($pipe) use ($key) {
            $pipe->incr($key);
            $pipe->expire($key, 60); // Keep data for 60 seconds
        });
    }

    /**
     * Handle AnswerUpdated events.
     */
    public function handleAnswerUpdated($event)
    {
        $this->incrementCounter($event->examId);
    }

    /**
     * Handle AttemptUpdated events.
     */
    public function handleAttemptUpdated($event)
    {
        $this->incrementCounter($event->examId);
    }

    /**
     * Handle CheatDetected events.
     */
    public function handleCheatDetected($event)
    {
        $this->incrementCounter($event->examId);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            \App\Events\Exam\AnswerUpdated::class => 'handleAnswerUpdated',
            \App\Events\Exam\AttemptUpdated::class => 'handleAttemptUpdated',
            \App\Events\Exam\CheatDetected::class => 'handleCheatDetected',
        ];
    }
}
