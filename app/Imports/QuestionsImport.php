<?php

namespace App\Imports;

use App\Models\Question;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;

class QuestionsImport implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading, ShouldQueue
{
    protected $tenant_id;
    protected $created_by;
    protected $subject_id;
    protected $class_id;

    public function __construct($tenant_id, $created_by, $subject_id = null, $class_id = null)
    {
        $this->tenant_id = $tenant_id;
        $this->created_by = $created_by;
        $this->subject_id = $subject_id;
        $this->class_id = $class_id;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Basic validation: question and at least 2 options must exist
        if (empty($row['question']) || empty($row['option_a']) || empty($row['option_b'])) {
            return null;
        }

        $options = [];
        $optionKeys = ['a', 'b', 'c', 'd', 'e'];
        $correctKey = strtoupper($row['correct'] ?? 'A');

        foreach ($optionKeys as $key) {
            $colName = 'option_' . $key;
            if (!empty($row[$colName])) {
                $options[] = [
                    'key' => strtoupper($key),
                    'text' => $row[$colName],
                    'is_correct' => (strtoupper($key) === $correctKey)
                ];
            }
        }

        // Ensure at least one correct answer exists, default to first if invalid
        $hasCorrect = collect($options)->contains('is_correct', true);
        if (!$hasCorrect && count($options) > 0) {
            $options[0]['is_correct'] = true;
        }

        return new Question([
            'tenant_id'   => $this->tenant_id,
            'subject_id'  => $this->subject_id,
            'class_id'    => $this->class_id,
            'type'        => 'mcq',
            'difficulty'  => strtolower($row['difficulty'] ?? 'medium'),
            'created_by'  => $this->created_by,
            'content'     => [
                'question_text' => $row['question'],
                'options'       => $options,
                'meta'          => ['latex' => true]
            ],
        ]);
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }
}
