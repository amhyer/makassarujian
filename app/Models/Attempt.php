<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attempt extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected static function booted()
    {
        static::saved(function ($attempt) {
            // Invalidate proctor dashboard cache when status changes
            if ($attempt->wasChanged('status')) {
                event(new \App\Events\Exam\AttemptUpdated($attempt));
            }
        });

        static::deleted(function ($attempt) {
            event(new \App\Events\Exam\AttemptUpdated($attempt));
        });
    }

    protected $casts = [
        'started_at'      => 'datetime',
        'expires_at'      => 'datetime',
        'completed_at'    => 'datetime',
        'last_synced_at'  => 'datetime',
        'result_snapshot' => 'array',
        // NOTE: 'answers' JSON column is deprecated — data now stored in attempt_answers table
    ];

    /**
     * Relationship to individual answers (normalized).
     * Satu attempt bisa punya banyak jawaban (1 per question).
     * Dipakai untuk: scoring, reporting, audit trail.
     * Nama "answerRecords" untuk menghindari bentrok dengan accessor answers.
     */
    public function answerRecords(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }

    /**
     * Accessor for backward compatibility: $attempt->answers returns array [question_id => selected_key]
     * Digunakan di view lama, API responses, dan compatibility layer.
     * Sumber data:
     *   1. Primary: attempt_answers table (via answerRecords relationship)
     *   2. Fallback: legacy JSON column 'answers' (belum dimigrasi)
     */
    public function getAnswersAttribute(): array
    {
        // 1. Try relationship (normalized) — loaded or query
        if ($this->relationLoaded('answerRecords')) {
            $fromRel = $this->answerRecords->pluck('selected_key', 'question_id')->toArray();
            if (!empty($fromRel)) {
                return $fromRel;
            }
        } else {
            // Query once
            $fromDb = $this->answerRecords()
                ->pluck('selected_key', 'question_id')
                ->toArray();
            if (!empty($fromDb)) {
                return $fromDb;
            }
        }

        // 2. Fallback to raw JSON column (legacy data not yet migrated)
        $raw = clone $this;
        $raw = $this->getAttributes()['answers'] ?? null;
        if ($raw) {
            $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * Relationship to Exam.
     */
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Relationship to User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the attempt has expired.
     */
    public function isExpired(): bool
    {
        if ($this->status === 'completed') return false;

        return $this->expires_at && now()->isAfter($this->expires_at);
    }

    /**
     * Get remaining seconds until expiry.
     */
    public function remainingSeconds(): int
    {
        if (!$this->expires_at || $this->isExpired()) return 0;

        return now()->diffInSeconds($this->expires_at, false);
    }
}
