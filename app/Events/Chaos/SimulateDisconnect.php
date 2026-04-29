<?php

namespace App\Events\Chaos;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SimulateDisconnect implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $examId;

    public function __construct($examId)
    {
        $this->examId = $examId;
    }

    public function broadcastOn()
    {
        if ($this->examId === 'all') {
            return new Channel('exam.global');
        }
        return new Channel('exam.' . $this->examId);
    }

    public function broadcastAs()
    {
        return 'chaos.disconnect';
    }
}
