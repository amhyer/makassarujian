<?php

namespace App\Events\Exam;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnswerUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $exam_id;
    public $user_id;
    public $progress;
    public $last_update;

    /**
     * Create a new event instance.
     */
    public function __construct($exam_id, $user_id, $progress)
    {
        $this->exam_id = $exam_id;
        $this->user_id = $user_id;
        $this->progress = $progress;
        $this->last_update = now()->toDateTimeString();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("exam.{$this->exam_id}"),
        ];
    }

    /**
     * Data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user_id,
            'progress' => $this->progress,
            'last_update' => $this->last_update,
        ];
    }
}
