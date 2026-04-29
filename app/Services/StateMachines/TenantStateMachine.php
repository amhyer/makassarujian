<?php

namespace App\Services\StateMachines;

use App\Models\Tenant;
use App\Enums\TenantStatus;
use InvalidArgumentException;

class TenantStateMachine
{
    /**
     * Cek apakah transisi dari status saat ini ke $to valid.
     */
    public function canTransition(Tenant $tenant, TenantStatus $to): bool
    {
        return in_array($to, $this->allowedTransitions($tenant->status));
    }

    /**
     * Lakukan transisi. Lempar exception jika tidak valid.
     */
    public function transition(Tenant $tenant, TenantStatus $to): void
    {
        if (! $this->canTransition($tenant, $to)) {
            throw new InvalidArgumentException(
                "Transisi tidak valid: [{$tenant->status->value}] → [{$to->value}]. " .
                "Transisi yang diizinkan: " . implode(', ', array_map(fn ($s) => $s->value, $this->allowedTransitions($tenant->status)))
            );
        }

        $tenant->status = $to;
        $tenant->save();
    }

    /**
     * Map transisi yang diizinkan per status.
     *
     * pending   → trial | active (force direct)
     * trial     → active | expired
     * active    → suspended | expired
     * expired   → active
     * suspended → active
     */
    private function allowedTransitions(TenantStatus $current): array
    {
        return match ($current) {
            TenantStatus::Pending   => [TenantStatus::Trial, TenantStatus::Active],
            TenantStatus::Trial     => [TenantStatus::Active, TenantStatus::Expired],
            TenantStatus::Active    => [TenantStatus::Suspended, TenantStatus::Expired],
            TenantStatus::Expired   => [TenantStatus::Active],
            TenantStatus::Suspended => [TenantStatus::Active],
        };
    }
}
