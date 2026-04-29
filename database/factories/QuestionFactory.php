<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'tenant_id' => Str::uuid(),
            'subject_id' => Str::uuid(),
            'class_id' => Str::uuid(),
            'type' => 'mcq',
            'content' => [
                'question_text' => $this->faker->paragraph,
                'options' => [
                    ['key' => 'A', 'text' => $this->faker->sentence, 'is_correct' => true],
                    ['key' => 'B', 'text' => $this->faker->sentence, 'is_correct' => false],
                ],
                'meta' => ['latex' => true]
            ],
            'explanation' => $this->faker->sentence,
            'difficulty' => 'medium',
            'created_by' => Str::uuid(),
        ];
    }
}
