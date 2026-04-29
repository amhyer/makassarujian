<?php

namespace App\Actions\Tenant;

use App\Enums\TenantStatus;
use App\Events\Tenant\TenantExpired;
use App\Models\AuditLog;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class ForceExpireTenant
{
    /**
     * Paksa expire tenant — tanpa validasi status (super admin override).
     */
    public function execute(Tenant $tenant): void
    {
        $tenant->status     = TenantStatus::Expired;
        $tenant->expired_at = now();
        $tenant->save();

        // Dispatch event
        TenantExpired::dispatch($tenant);

        // Audit log
        AuditLog::record('tenant.force_expired', [
            'tenant_name' => $tenant->name,
            'expired_at'  => $tenant->expired_at->toDateTimeString(),
        ], $tenant->id);

        Log::info("[FORCE EXPIRED] {$tenant->name}");
    }
}
