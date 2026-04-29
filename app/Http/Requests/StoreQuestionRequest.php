<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'exists:subjects,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'content' => ['required', 'json'],
            'explanation' => ['nullable', 'string'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check if content is valid JSON before decoding
            if ($validator->errors()->has('content')) {
                return;
            }

            $content = json_decode($this->content, true);
            
            // Basic Structure Check
            if (!isset($content['question_text']) || empty($content['question_text'])) {
                $validator->errors()->add('content', 'Teks pertanyaan wajib diisi dalam struktur JSON.');
            }

            // MCQ Specific Validation
            if (($this->type ?? 'mcq') === 'mcq') {
                $options = $content['options'] ?? [];
                $optionCount = count($options);

                // Option range: 2 - 5
                if ($optionCount < 2 || $optionCount > 5) {
                    $validator->errors()->add('content', "Jumlah pilihan jawaban harus antara 2 sampai 5 (Saat ini: {$optionCount}).");
                }

                // Exactly 1 correct answer
                $correctCount = collect($options)->where('is_correct', true)->count();
                if ($correctCount !== 1) {
                    $validator->errors()->add('content', "Harus ada tepat 1 jawaban yang benar (Saat ini: {$correctCount}).");
                }

                // Option text check
                foreach ($options as $index => $opt) {
                    if (empty($opt['text'])) {
                        $validator->errors()->add('content', "Teks pada Opsi " . ($opt['key'] ?? ($index + 1)) . " tidak boleh kosong.");
                    }
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'content.json' => 'Format data konten tidak valid (Harus JSON).',
            'subject_id.required' => 'Mata pelajaran wajib dipilih.',
            'class_id.required' => 'Kelas wajib dipilih.',
        ];
    }
}
