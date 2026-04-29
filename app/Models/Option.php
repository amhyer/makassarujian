<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Option extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'question_id',
        'content',
        'is_correct',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    /**
     * Relationship to Question.
     */
    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
