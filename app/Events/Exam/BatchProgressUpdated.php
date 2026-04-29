<?php

namespace App\Events\Exam;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BatchProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $examId;
    public $updates;

    /**
     * Create a new event instance.
     * @param string $examId
     * @param array $updates Array of ['user_id' => ..., 'progress' => ...]
     */
    public function __construct($examId, array $updates)
    {
        $this->examId = $examId;
        $this->updates = $updates;
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
            'batch' => $this->updates,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
