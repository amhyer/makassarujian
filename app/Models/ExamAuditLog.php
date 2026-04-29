<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAuditLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Relationship to Attempt.
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class);
    }
}
