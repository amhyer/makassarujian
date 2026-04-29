<?php

namespace App\Actions\Tenant;

use App\Enums\TenantStatus;
use App\Models\AuditLog;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ExtendTenantTrial
{
    /**
     * Perpanjang masa trial tenant.
     *
     * @param int $days Jumlah hari penambahan (1–365)
     * @throws InvalidArgumentException jika status tidak memungkinkan perpanjangan
     */
    public function execute(Tenant $tenant, int $days): void
    {
        if (! in_array($tenant->status, [TenantStatus::Trial, TenantStatus::Active])) {
            throw new InvalidArgumentException(
                "Perpanjangan trial hanya tersedia untuk tenant berstatus Trial atau Aktif. " .
                "Status saat ini: [{$tenant->status->label()}]."
            );
        }

        // Hitung base date: jika trial_ends_at masih di masa depan, extend dari situ
        $base = $tenant->trial_ends_at && $tenant->trial_ends_at->isFuture()
            ? $tenant->trial_ends_at
            : Carbon::now();

        $tenant->trial_ends_at = $base->addDays($days);
        $tenant->save();

        // Audit log
        AuditLog::record('tenant.trial_extended', [
            'tenant_name'   => $tenant->name,
            'days_added'    => $days,
            'new_trial_end' => $tenant->trial_ends_at->toDateString(),
        ], $tenant->id);

        Log::info("[TRIAL EXTENDED] {$tenant->name}: +{$days} hari → {$tenant->trial_ends_at->toDateString()}");
    }
}
