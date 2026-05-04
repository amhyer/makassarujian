<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tenant_invite_codes
 *
 * Setiap sekolah (tenant) dapat membuat kode undangan untuk siswa.
 * Siswa wajib memasukkan kode saat registrasi — tidak ada registrasi terbuka.
 *
 * Kolom penting:
 *   code        — 8 karakter uppercase unik (e.g. "SMKN1MKS")
 *   tenant_id   — kode ini milik sekolah mana
 *   expires_at  — opsional: kode kadaluarsa otomatis
 *   max_uses    — opsional: batasi jumlah pemakaian (null = unlimited)
 *   used_count  — counter pemakaian (dinaikkan atomic via DB increment)
 *   is_active   — admin bisa menonaktifkan kode tanpa menghapusnya
 *   created_by  — user_id admin yang membuat kode (audit trail)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_invite_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();

            $table->string('code', 16)->unique();          // Kode undangan unik
            $table->timestamp('expires_at')->nullable();   // Null = tidak ada batas waktu
            $table->unsignedInteger('max_uses')->nullable(); // Null = unlimited
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('label')->nullable();           // Nama deskriptif (mis: "Kelas XII IPA 1")

            $table->timestamps();

            // Index untuk pencarian cepat saat validasi di registrasi
            $table->index(['code', 'is_active']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_invite_codes');
    }
};
