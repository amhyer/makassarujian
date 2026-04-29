<?php

namespace App\Events\Tenant;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImpersonationStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $originalAdmin,
        public readonly User $impersonatedUser,
        public readonly Tenant $tenant,
    ) {}
}
