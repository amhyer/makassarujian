<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\TenantInviteCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * RegisterController
 *
 * Registrasi siswa WAJIB menggunakan invite code yang valid.
 * Tidak ada registrasi terbuka — tenant_id selalu di-set dari kode undangan,
 * bukan dari input user.
 *
 * SECURITY GUARANTEES:
 *   1. tenant_id TIDAK pernah diambil dari request (tidak bisa di-manipulasi)
 *   2. Kode undangan divalidasi dalam DB transaction + DB lock agar
 *      concurrent request tidak melebihi max_uses
 *   3. used_count di-increment secara atomic via DB::increment
 *   4. Kode expired / nonaktif / habis quota → 422 dengan pesan yang jelas
 */
class RegisterController extends Controller
{
    /**
     * Show the registration form.
     * Jika URL punya ?invite=KODE, pre-fill kolom kode.
     */
    public function create(Request $request)
    {
        return view('auth.register', [
            'inviteCode' => $request->query('invite'),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * Flow:
     *   1. Validasi input dasar (nama, email, password)
     *   2. Validasi invite_code — cari di DB, pastikan usable
     *   3. Buka DB transaction:
     *      a. Lock row invite code (SELECT FOR UPDATE)
     *      b. Re-check usability di dalam lock (mencegah race condition)
     *      c. Create user dengan tenant_id dari kode (BUKAN dari request)
     *      d. Assign role Student
     *      e. Increment used_count secara atomic
     *   4. Login user
     */
    public function store(Request $request)
    {
        // ── STEP 1: Validasi input dasar ──────────────────────────────────
        $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'    => ['required', 'confirmed', Password::defaults()],
            'invite_code' => ['required', 'string', 'size:8'],
        ]);

        // ── STEP 2: Cari kode undangan (case-insensitive) ─────────────────
        $inviteCode = TenantInviteCode::where('code', strtoupper($request->invite_code))
            ->with('tenant')
            ->first();

        if (! $inviteCode) {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['invite_code' => 'Kode undangan tidak ditemukan.']);
        }

        // Pre-check sebelum masuk transaction (menghemat lock waktu)
        if (! $inviteCode->isUsable()) {
            $reason = $this->resolveInvalidReason($inviteCode);
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['invite_code' => $reason]);
        }

        // ── STEP 3: DB Transaction dengan lock ───────────────────────────
        // Wrap dalam transaction agar: jika user create gagal, used_count
        // tidak ikut bertambah. Lock mencegah dua siswa memakai sisa 1 quota.
        $user = DB::transaction(function () use ($request, $inviteCode) {
            // Lock row invite code untuk durasi transaction ini
            $locked = TenantInviteCode::lockForUpdate()->find($inviteCode->id);

            // Re-check di dalam lock — kondisi bisa berubah sejak pre-check
            if (! $locked->isUsable()) {
                $reason = $this->resolveInvalidReason($locked);
                throw new \RuntimeException($reason);
            }

            // Buat user — tenant_id dari kode, BUKAN dari request
            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'tenant_id' => $locked->tenant_id, // ← source of truth
                'status'    => 'active',
            ]);

            $user->assignRole('Student');

            // Increment atomic — aman untuk concurrent request
            $locked->incrementUsage();

            return $user;
        });

        auth()->login($user);

        return redirect()->route('dashboard');
    }

    /**
     * Resolusi pesan error yang informatif berdasarkan kondisi kode.
     */
    private function resolveInvalidReason(TenantInviteCode $code): string
    {
        if (! $code->is_active) {
            return 'Kode undangan ini sudah tidak aktif. Hubungi admin sekolah Anda.';
        }

        if ($code->expires_at && now()->greaterThan($code->expires_at)) {
            return 'Kode undangan ini sudah kadaluarsa. Hubungi admin sekolah untuk kode baru.';
        }

        if ($code->max_uses !== null && $code->used_count >= $code->max_uses) {
            return 'Kode undangan ini sudah mencapai batas pemakaian maksimum.';
        }

        return 'Kode undangan tidak valid.';
    }
}
