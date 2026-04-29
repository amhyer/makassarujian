<?php

namespace App\Events\Tenant;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TenantCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly User $adminUser,
    ) {}
}
