<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Tenant;

class TenantPolicy
{
    /**
     * Hanya Super Admin yang bisa mengelola semua tenant.
     */
    public function manage(User $user): bool
    {
        return $user->hasRole('Super Admin');
    }

    /**
     * Admin Sekolah hanya bisa melihat tenant miliknya sendiri.
     */
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->hasRole('Super Admin') || $user->tenant_id === $tenant->id;
    }

    /**
     * Update tenant: hanya Super Admin.
     */
    public function update(User $user, Tenant $tenant): bool
    {
        return $user->hasRole('Super Admin');
    }

    /**
     * Impersonate: hanya Super Admin & tidak sedang dalam sesi impersonation.
     */
    public function impersonate(User $user, Tenant $tenant): bool
    {
        return $user->hasRole('Super Admin') && ! session('impersonating');
    }

    /**
     * Activate/expire/suspend: hanya Super Admin & tidak sedang impersonating.
     */
    public function changeStatus(User $user, Tenant $tenant): bool
    {
        return $user->hasRole('Super Admin') && ! session('impersonating');
    }
}
