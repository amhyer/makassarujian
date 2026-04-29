<?php

namespace App\Actions\Tenant;

use App\Enums\TenantStatus;
use App\Events\Tenant\TenantActivated;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Services\StateMachines\TenantStateMachine;
use Illuminate\Support\Facades\Log;

class ActivateTenant
{
    public function __construct(
        protected TenantStateMachine $stateMachine
    ) {}

    /**
     * Aktivasi tenant.
     *
     * @param bool $force  Jika true, bypass state machine (untuk Pending → Active langsung)
     */
    public function execute(Tenant $tenant, bool $force = false): void
    {
        if ($force) {
            // Bypass state machine — set langsung
            $tenant->status       = TenantStatus::Active;
            $tenant->activated_at = now();
            $tenant->expired_at   = now()->addYear();
            $tenant->save();
        } else {
            // Lewat state machine normal
            $this->stateMachine->transition($tenant, TenantStatus::Active);
            $tenant->activated_at = now();
            $tenant->expired_at   = now()->addYear();
            $tenant->save();
        }

        // Dispatch event
        TenantActivated::dispatch($tenant, $force);

        // Audit log
        AuditLog::record('tenant.activated', [
            'tenant_name' => $tenant->name,
            'forced'      => $force,
            'status'      => $tenant->status->value,
        ], $tenant->id);

        Log::info("[TENANT ACTIVATED] {$tenant->name} (force={$force})");
    }
}
