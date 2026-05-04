<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TenantInviteCode extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active'  => 'boolean',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Guard Helpers ───────────────────────────────────────────────────────

    /**
     * Apakah kode ini masih valid untuk dipakai?
     * Cek: aktif + belum expired + belum habis quota.
     */
    public function isUsable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && now()->greaterThan($this->expires_at)) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Tambahkan used_count secara atomic (race-safe).
     * Menggunakan DB increment agar tidak ada lost update saat concurrent request.
     */
    public function incrementUsage(): void
    {
        static::where('id', $this->id)->increment('used_count');
        $this->used_count++;
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeUsable($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')
                  ->orWhereRaw('used_count < max_uses');
            });
    }
}
