<?php

namespace App\Actions\Tenant;

use App\Events\Tenant\ImpersonationStopped;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class StopImpersonation
{
    /**
     * Kembalikan sesi ke Super Admin yang asli.
     *
     * @throws RuntimeException jika tidak sedang impersonating
     */
    public function execute(): void
    {
        if (! session('impersonating')) {
            throw new RuntimeException('Tidak sedang dalam sesi impersonation.');
        }

        $originalAdminId = session('impersonated_by');
        $originalAdmin   = User::findOrFail($originalAdminId);

        $currentUser = Auth::user();

        // 1. Hapus semua session flags impersonation
        session()->forget(['impersonating', 'impersonated_by', 'impersonated_tenant']);

        // 2. Login kembali sebagai original admin
        Auth::login($originalAdmin);

        // 3. Dispatch event
        ImpersonationStopped::dispatch($originalAdmin);

        // 4. Audit log
        AuditLog::record('impersonate.stop', [
            'original_admin' => $originalAdmin->email,
            'was_impersonating' => $currentUser?->email,
        ]);

        \App\Models\ImpersonationLog::where('impersonator_id', $originalAdmin->id)
            ->where('impersonated_user_id', $currentUser?->id)
            ->whereNull('ended_at')
            ->latest()
            ->first()
            ?->update(['ended_at' => now()]);

        Log::info("[IMPERSONATE STOP] Kembali ke {$originalAdmin->email}");
    }
}
