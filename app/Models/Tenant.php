<?php

namespace App\Models;

use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'activated_at'  => 'datetime',
        'expired_at'    => 'datetime',
        'status'        => TenantStatus::class,
    ];

    // ─── Relationships ───────────────────────────────────────────────────────

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function users()
    {
        return $this->hasMany(\App\Models\User::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeSchools($query) { return $query->where('type', 'school'); }
    public function scopeFkgg($query)    { return $query->where('type', 'fkkg'); }

    public function scopePending($query)   { return $query->where('status', TenantStatus::Pending->value); }
    public function scopeTrial($query)     { return $query->where('status', TenantStatus::Trial->value); }
    public function scopeActive($query)    { return $query->where('status', TenantStatus::Active->value); }
    public function scopeExpired($query)   { return $query->where('status', TenantStatus::Expired->value); }
    public function scopeSuspended($query) { return $query->where('status', TenantStatus::Suspended->value); }

    // ─── Helper Methods ──────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === TenantStatus::Active;
    }

    public function isTrial(): bool
    {
        return $this->status === TenantStatus::Trial;
    }

    public function isExpired(): bool
    {
        return $this->status === TenantStatus::Expired;
    }

    public function isSuspended(): bool
    {
        return $this->status === TenantStatus::Suspended;
    }

    public function isPending(): bool
    {
        return $this->status === TenantStatus::Pending;
    }

    /**
     * Hitung sisa hari dari trial_ends_at.
     * Negatif berarti sudah lewat.
     */
    public function daysRemaining(): ?int
    {
        if ($this->trial_ends_at) {
            return (int) now()->diffInDays($this->trial_ends_at, false);
        }
        return null;
    }

    /**
     * Apakah masa trial akan berakhir dalam N hari ke depan?
     */
    public function isExpiringSoon(int $days = 3): bool
    {
        return $this->isTrial()
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture()
            && $this->trial_ends_at->diffInDays(now()) <= $days;
    }
}
