<?php

namespace App\Events\Exam;

use App\Models\Attempt;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttemptUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $attempt;

    /**
     * Create a new event instance.
     */
    public function __construct(Attempt $attempt)
    {
        $this->attempt = $attempt;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("exam.{$this->attempt->exam_id}"),
        ];
    }

    /**
     * Data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->attempt->user_id,
            'status' => $this->attempt->status,
            'updated_at' => $this->attempt->updated_at->toDateTimeString(),
        ];
    }
}
