<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'question_id' => Str::uuid(), // Will be overridden
            'content' => $this->faker->sentence,
            'is_correct' => false,
        ];
    }
}
