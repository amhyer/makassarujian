<?php

namespace App\Events\Exam;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CheatDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $exam_id;
    public $user_id;
    public $type;
    public $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct($exam_id, $user_id, $type)
    {
        $this->exam_id = $exam_id;
        $this->user_id = $user_id;
        $this->type = $type;
        $this->timestamp = now()->toDateTimeString();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("exam.{$this->exam_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user_id,
            'type' => $this->type,
            'timestamp' => $this->timestamp,
            'message' => "Siswa terdeteksi melakukan: {$this->type}"
        ];
    }
}
