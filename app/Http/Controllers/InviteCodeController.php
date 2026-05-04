<?php

namespace App\Http\Controllers;

use App\Models\TenantInviteCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * InviteCodeController
 *
 * Digunakan oleh School Admin untuk mengelola kode undangan siswa.
 * Hanya admin dari tenant yang sama yang bisa melihat / membuat kode milik tenantnya.
 */
class InviteCodeController extends Controller
{
    /**
     * List semua kode undangan milik tenant yang sedang login.
     */
    public function index()
    {
        $codes = TenantInviteCode::where('tenant_id', Auth::user()->tenant_id)
            ->with('creator:id,name')
            ->latest()
            ->paginate(20);

        return view('pages.invite-codes.index', compact('codes'));
    }

    /**
     * Buat kode undangan baru.
     * Kode di-generate otomatis (8 karakter uppercase + angka) — unik di DB.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'label'     => 'nullable|string|max:100',
            'expires_at' => 'nullable|date|after:now',
            'max_uses'  => 'nullable|integer|min:1|max:10000',
        ]);

        // Generate kode unik — loop sampai dapat yang belum dipakai
        do {
            $code = strtoupper(Str::random(8));
        } while (TenantInviteCode::where('code', $code)->exists());

        TenantInviteCode::create([
            'tenant_id'  => Auth::user()->tenant_id,
            'created_by' => Auth::id(),
            'code'       => $code,
            'label'      => $validated['label'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'max_uses'   => $validated['max_uses'] ?? null,
        ]);

        return back()->with('success', "Kode undangan **{$code}** berhasil dibuat.");
    }

    /**
     * Nonaktifkan kode (soft-disable, tidak dihapus agar audit trail terjaga).
     */
    public function deactivate(TenantInviteCode $inviteCode)
    {
        // Tenant isolation — pastikan kode ini milik tenant yang sedang login
        abort_if($inviteCode->tenant_id !== Auth::user()->tenant_id, 403);

        $inviteCode->update(['is_active' => false]);

        return back()->with('success', 'Kode undangan telah dinonaktifkan.');
    }

    /**
     * Aktifkan kembali kode yang dinonaktifkan.
     */
    public function activate(TenantInviteCode $inviteCode)
    {
        abort_if($inviteCode->tenant_id !== Auth::user()->tenant_id, 403);

        $inviteCode->update(['is_active' => true]);

        return back()->with('success', 'Kode undangan telah diaktifkan kembali.');
    }
}
