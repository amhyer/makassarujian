<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\Exam\PanicModeActivated;
use App\Models\Attempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * PanicController
 *
 * Provides "Panic Button" capability for School Admins and Proctors.
 *
 * DESIGN:
 *  - activate(): Pause all ongoing attempts, broadcast panic event to all WS clients.
 *  - deactivate(): Resume exam sessions, broadcast clear signal.
 *  - status(): Query current panic state for the tenant.
 *
 * SECURITY:
 *  - Scoped strictly to the authenticated user's tenant_id.
 *  - Only 'School Admin' or 'Super Admin' roles can activate.
 *  - Rate-limited (3 activations per minute — prevents accidental spam).
 */
class PanicController extends Controller
{
    private function panicKey(string $tenantId): string
    {
        return "panic_mode:{$tenantId}";
    }

    /**
     * Activate Panic Mode: pause all exams, broadcast to all students.
     */
    public function activate(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        // ── AUTHORIZATION: Only Admin roles may activate ──────────────────────
        if (!$user->hasAnyRole(['School Admin', 'Super Admin', 'FKKG Admin'])) {
            return response()->json(['message' => 'Tidak memiliki izin untuk mengaktifkan Panic Mode.'], 403);
        }

        $panicKey = $this->panicKey($tenantId);

        if (Cache::has($panicKey)) {
            return response()->json([
                'message' => 'Panic Mode sudah aktif.',
                'status'  => 'already_active',
            ], 409);
        }

        // ── SET PANIC FLAG in Redis (TTL: 2 jam — max exam duration) ─────────
        Cache::put($panicKey, [
            'activated_by' => $user->name,
            'message'      => $request->message,
            'activated_at' => now()->toIso8601String(),
        ], now()->addHours(2));

        // ── PAUSE ALL ONGOING ATTEMPTS for this tenant ────────────────────────
        $pausedCount = Attempt::where('tenant_id', $tenantId)
            ->where('status', 'ongoing')
            ->update(['status' => 'paused']);

        // ── BROADCAST PANIC SIGNAL to all connected students ─────────────────
        try {
            broadcast(new PanicModeActivated($tenantId, $request->message, $user->name));
        } catch (\Exception $e) {
            Log::warning("PanicMode broadcast failed: " . $e->getMessage());
        }

        Log::critical("PANIC MODE ACTIVATED", [
            'tenant_id'    => $tenantId,
            'activated_by' => $user->name,
            'message'      => $request->message,
            'paused_count' => $pausedCount,
        ]);

        return response()->json([
            'status'        => 'panic_activated',
            'message'       => 'Panic Mode aktif. Semua ujian dijeda dan pesan dikirim ke siswa.',
            'paused_count'  => $pausedCount,
        ]);
    }

    /**
     * Deactivate Panic Mode: resume all paused exams.
     */
    public function deactivate(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasAnyRole(['School Admin', 'Super Admin', 'FKKG Admin'])) {
            return response()->json(['message' => 'Tidak memiliki izin.'], 403);
        }

        $panicKey = $this->panicKey($tenantId);

        if (!Cache::has($panicKey)) {
            return response()->json(['message' => 'Panic Mode tidak aktif.', 'status' => 'not_active'], 409);
        }

        Cache::forget($panicKey);

        // Resume only paused attempts (not 'completed')
        $resumedCount = Attempt::where('tenant_id', $tenantId)
            ->where('status', 'paused')
            ->update(['status' => 'ongoing']);

        // Broadcast recovery signal
        try {
            broadcast(new \App\Events\Exam\PanicModeActivated(
                $tenantId,
                '__PANIC_DEACTIVATED__',
                $user->name
            ));
        } catch (\Exception $e) {
            Log::warning("PanicMode deactivate broadcast failed: " . $e->getMessage());
        }

        Log::info("PANIC MODE DEACTIVATED", [
            'tenant_id'    => $tenantId,
            'deactivated_by' => $user->name,
            'resumed_count'  => $resumedCount,
        ]);

        return response()->json([
            'status'        => 'panic_deactivated',
            'message'       => 'Panic Mode dinonaktifkan. Semua ujian dilanjutkan.',
            'resumed_count' => $resumedCount,
        ]);
    }

    /**
     * Get current panic status for the tenant.
     */
    public function status()
    {
        $tenantId = Auth::user()->tenant_id;
        $panicData = Cache::get($this->panicKey($tenantId));

        return response()->json([
            'panic_active' => (bool) $panicData,
            'data'         => $panicData,
        ]);
    }
}
