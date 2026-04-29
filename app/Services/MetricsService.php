<?php

namespace App\Services;

use App\Models\Tenant;
use App\Enums\TenantStatus;

class MetricsService
{
    /**
     * Metrik ringkasan untuk halaman Manajemen Sekolah.
     */
    public function getSchoolMetrics(): array
    {
        $base = Tenant::schools();

        return [
            'total'    => (clone $base)->count(),
            'active'   => (clone $base)->where('status', TenantStatus::Active->value)->count(),
            'trial'    => (clone $base)->where('status', TenantStatus::Trial->value)->count(),
            'expired'  => (clone $base)->where('status', TenantStatus::Expired->value)->count(),
            'pending'  => (clone $base)->where('status', TenantStatus::Pending->value)->count(),
        ];
    }

    /**
     * Metrik ringkasan untuk halaman Manajemen FKGG.
     */
    public function getFkkgMetrics(): array
    {
        $base = Tenant::fkgg();

        return [
            'total'      => (clone $base)->count(),
            'active'     => (clone $base)->where('status', TenantStatus::Active->value)->count(),
            'trial'      => (clone $base)->where('status', TenantStatus::Trial->value)->count(),
            'questions'  => 0,   // Placeholder — akan diisi saat modul Ujian dibangun
            'tryouts'    => 0,   // Placeholder — akan diisi saat modul Tryout dibangun
            'schools'    => 0,   // Placeholder — distribusi ke sekolah
        ];
    }

    /**
     * Metrik ringkasan untuk halaman Aktivasi & Status.
     */
    public function getActivationMetrics(): array
    {
        return [
            'total'    => Tenant::count(),
            'active'   => Tenant::where('status', TenantStatus::Active->value)->count(),
            'trial'    => Tenant::where('status', TenantStatus::Trial->value)->count(),
            'expired'  => Tenant::where('status', TenantStatus::Expired->value)->count(),
            'pending'  => Tenant::where('status', TenantStatus::Pending->value)->count(),
            'expiring_soon' => Tenant::where('status', TenantStatus::Trial->value)
                ->where('trial_ends_at', '<=', now()->addDays(3))
                ->where('trial_ends_at', '>=', now())
                ->count(),
        ];
    }
}
