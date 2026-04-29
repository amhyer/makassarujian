<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Tenant\Traits\BelongsToTenant;

class Question extends Model
{
    use HasUuids, BelongsToTenant, SoftDeletes, HasFactory;

    protected $fillable = [
        'tenant_id',
        'subject_id',
        'class_id',
        'type',
        'content',
        'explanation',
        'difficulty',
        'created_by',
    ];

    protected $casts = [
        'content' => 'array',
    ];

    /**
     * Accessor for options extracted from JSON content.
     */
    public function getOptionsAttribute()
    {
        return $this->content['options'] ?? [];
    }

    /**
     * Accessor for question text extracted from JSON content.
     */
    public function getQuestionTextAttribute()
    {
        return $this->content['question_text'] ?? '';
    }

    /**
     * Relationship to Subject.
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Relationship to Classes.
     */
    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    /**
     * Relationship to Creator (User).
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship to Exams.
     */
    public function exams()
    {
        return $this->belongsToMany(Exam::class, 'exam_questions');
    }

    // ─── Phase 2 Helpers (Exam Engine) ───────────────────────────────────

    /**
     * Check if the question is Multiple Choice.
     */
    public function isMultipleChoice(): bool
    {
        return $this->type === 'mcq';
    }

    /**
     * Get the correct answer object/array.
     */
    public function correctAnswer()
    {
        return collect($this->options)->firstWhere('is_correct', true);
    }

    /**
     * Return shuffled options for the exam session.
     * This ensures each student gets a different order.
     */
    public function shuffleOptions(): array
    {
        return collect($this->options)->shuffle()->toArray();
    }
}
