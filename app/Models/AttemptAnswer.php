<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Tenant\Traits\BelongsToTenant;

class AttemptAnswer extends Model
{
    use HasUuids, BelongsToTenant;

    protected $guarded = ['id'];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'attempt_id',
        'question_id',
        'tenant_id',
        'selected_key',
        'answered_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'answered_at' => 'datetime',
    ];

    /**
     * Relationship to Attempt.
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class);
    }

    /**
     * Relationship to Question.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Relationship to Tenant (for tenant scoping).
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope for tenant isolation.
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope for specific attempt.
     */
    public function scopeForAttempt($query, $attemptId)
    {
        return $query->where('attempt_id', $attemptId);
    }
};
