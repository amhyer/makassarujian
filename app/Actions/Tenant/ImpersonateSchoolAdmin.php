<?php

namespace App\Actions\Tenant;

use App\Events\Tenant\ImpersonationStarted;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ImpersonateSchoolAdmin
{
    /**
     * Login sebagai admin sekolah dari tenant yang dituju.
     *
     * @throws InvalidArgumentException jika tidak ada admin di tenant ini
     */
    public function execute(Tenant $tenant): void
    {
        // 1. Cari admin user dari tenant
        $targetAdmin = User::where('tenant_id', $tenant->id)
            ->where('is_tenant_admin', true)
            ->first();

        if (! $targetAdmin) {
            throw new InvalidArgumentException(
                "Tidak ada admin aktif untuk sekolah [{$tenant->name}]. Tambahkan akun admin terlebih dahulu."
            );
        }

        $originalAdmin = Auth::user();

        // 2. Simpan session flags impersonation
        session([
            'impersonating'       => true,
            'impersonated_by'     => $originalAdmin->id,
            'impersonated_tenant' => $tenant->id,
        ]);

        // 3. Login sebagai target admin
        Auth::login($targetAdmin);

        // 4. Dispatch event
        ImpersonationStarted::dispatch($originalAdmin, $targetAdmin, $tenant);

        // 5. Audit log
        AuditLog::record('impersonate.start', [
            'original_admin'   => $originalAdmin->email,
            'target_admin'     => $targetAdmin->email,
            'tenant_name'      => $tenant->name,
        ], $tenant->id);

        \App\Models\ImpersonationLog::create([
            'impersonator_id' => $originalAdmin->id,
            'impersonated_user_id' => $targetAdmin->id,
            'started_at' => now(),
            'ip_address' => request()->ip(),
        ]);

        Log::info("[IMPERSONATE] {$originalAdmin->email} login sebagai {$targetAdmin->email} (Tenant: {$tenant->name})");
    }
}
