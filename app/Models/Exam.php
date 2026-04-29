<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Modules\Tenant\Traits\BelongsToTenant;

class Exam extends Model
{
    use HasUuids, BelongsToTenant;

    protected $guarded = ['id'];

    protected $casts = [
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    /**
     * Relationship to Subject.
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Relationship to Questions (Pivot).
     */
    public function questions()
    {
        return $this->belongsToMany(Question::class, 'exam_questions')
                    ->withPivot('order')
                    ->withTimestamps();
    }

    /**
     * Prepare questions for an exam attempt.
     * Handles selection and shuffling if enabled.
     */
    public function getQuestionsForExam()
    {
        $query = $this->questions();

        if (!$this->shuffle_questions) {
            $query->orderBy('exam_questions.order');
        }

        $questions = $query->get();

        if ($this->shuffle_questions) {
            $questions = $questions->shuffle();
        }

        return $questions;
    }

    /**
     * Apply shuffling logic to a collection of questions.
     * This is useful for real-time exam generation.
     */
    public function applyShuffle($questions)
    {
        if ($this->shuffle_questions) {
            $questions = $questions->shuffle();
        }

        if ($this->shuffle_options) {
            $questions->each(function ($question) {
                $question->shuffled_options = $question->shuffleOptions();
            });
        }

        return $questions;
    }
}
