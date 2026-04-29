<?php

namespace App\Events\Tenant;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImpersonationStopped
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $originalAdmin,
    ) {}
}
