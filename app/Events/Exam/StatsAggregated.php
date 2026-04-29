<?php

namespace App\Events\Exam;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StatsAggregated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $examId;
    public $stats;

    /**
     * Create a new event instance.
     */
    public function __construct($examId, array $stats)
    {
        $this->examId = $examId;
        $this->stats = $stats;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("exam.{$this->examId}"),
        ];
    }

    /**
     * Data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'stats' => $this->stats,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
