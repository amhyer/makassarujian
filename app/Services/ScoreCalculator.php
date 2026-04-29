<?php

namespace App\Services;

use App\Models\Attempt;
use App\Models\AttemptAnswer;
use Illuminate\Support\Collection;

/**
 * ScoreCalculator — Synchronous, snapshot-based scoring.
 *
 * Rules:
 *  - Score is calculated ONCE at submit time and frozen.
 *  - Never recalculated from DB afterward (result_snapshot is source of truth).
 *  - Each question yields: correct, wrong, or skipped.
 *  - Answers source: attempt_answers table (normalized), with fallback to legacy JSON column.
 */
class ScoreCalculator
{
    /**
     * Calculate score and return a frozen result snapshot.
     *
     * @param  Attempt     $attempt   The just-submitted attempt (with exam relation loaded)
     * @param  Collection  $questions Exam questions with 'correct_option' field
     * @return array{score: float, total_correct: int, total_questions: int, snapshot: array}
     */
    public function calculate(Attempt $attempt, Collection $questions): array
    {
        // --- READ ANSWERS FROM NORMALIZED TABLE (preferred) ---
        $answers = AttemptAnswer::where('attempt_id', $attempt->id)
            ->pluck('selected_key', 'question_id')
            ->toArray();

        // --- FALLBACK: legacy JSON column (untuk data lama yang belum dimigrasi) ---
        if (empty($answers) && $attempt->getAttribute('answers')) {
            $legacy = $attempt->getAttribute('answers');
            if (is_array($legacy)) {
                $answers = $legacy;
            }
        }

        $totalQuestions = $questions->count();
        $totalCorrect   = 0;
        $breakdown      = [];

        foreach ($questions as $question) {
            $qid      = (string) $question->id;
            $given    = $answers[$qid] ?? null;
            $correct  = $question->correct_option;
            $isRight  = $given !== null && $given === $correct;

            if ($isRight) {
                $totalCorrect++;
            }

            $breakdown[$qid] = [
                'question_id'  => $qid,
                'given'        => $given,
                'correct'      => $correct,
                'is_correct'   => $isRight,
                'status'       => $given === null ? 'skipped' : ($isRight ? 'correct' : 'wrong'),
            ];
        }

        $score = $totalQuestions > 0
            ? round(($totalCorrect / $totalQuestions) * 100, 2)
            : 0.00;

        return [
            'score'           => $score,
            'total_correct'   => $totalCorrect,
            'total_questions' => $totalQuestions,
            'snapshot'        => $breakdown,
        ];
    }

    /**
     * Convenience: calculate and persist the snapshot onto the attempt.
     */
    public function calculateAndPersist(Attempt $attempt): array
    {
        // Load questions with correct answers (select only needed columns)
        $questions = $attempt->exam()
            ->questions()
            ->select(['questions.id', 'correct_option'])
            ->get();

        $result = $this->calculate($attempt, $questions);

        $attempt->update([
            'score'           => $result['score'],
            'total_correct'   => $result['total_correct'],
            'total_questions' => $result['total_questions'],
            'result_snapshot' => $result['snapshot'],
        ]);

        return $result;
    }
}
