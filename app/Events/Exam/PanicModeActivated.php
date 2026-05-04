<?php

namespace App\Events\Exam;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PanicModeActivated
 *
 * Broadcast to ALL exam channels under a tenant when the admin
 * activates Panic Mode. Students receive an overlay with the
 * admin's message and exam interactions are blocked on the frontend.
 */
class PanicModeActivated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $tenantId;
    public string $message;
    public string $activatedBy;
    public string $timestamp;

    public function __construct(string $tenantId, string $message, string $activatedBy)
    {
        $this->tenantId    = $tenantId;
        $this->message     = $message;
        $this->activatedBy = $activatedBy;
        $this->timestamp   = now()->toIso8601String();
    }

    public function broadcastOn(): array
    {
        // Broadcast to the tenant-wide channel so ALL active exam sessions receive it
        return [
            new Channel("tenant.{$this->tenantId}.broadcast"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'panic.activated';
    }

    public function broadcastWith(): array
    {
        return [
            'message'      => $this->message,
            'activated_by' => $this->activatedBy,
            'timestamp'    => $this->timestamp,
        ];
    }
}
