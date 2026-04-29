<?php

namespace App\Services;

use App\Models\Tenant;
use App\Enums\TenantStatus;

class TenantService
{
    public function createTenant(array $data): Tenant
    {
        return Tenant::create(array_merge([
            'status' => TenantStatus::Pending->value,
        ], $data));
    }

    public function updateTenant(Tenant $tenant, array $data): Tenant
    {
        $tenant->update($data);
        return $tenant->fresh();
    }

    public function deleteTenant(Tenant $tenant): void
    {
        $tenant->delete();
    }
}
