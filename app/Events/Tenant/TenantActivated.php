<?php

namespace App\Events\Tenant;

use App\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TenantActivated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly bool $forced = false,
    ) {}
}
