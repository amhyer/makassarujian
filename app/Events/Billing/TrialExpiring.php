<?php

namespace App\Events\Billing;

use App\Models\Subscription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrialExpiring
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Subscription $subscription) {}
}
