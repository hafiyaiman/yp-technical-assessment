<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\Exam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Question>
 */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'type' => QuestionType::MultipleChoice,
            'prompt' => fake()->sentence(),
            'points' => 1,
            'position' => 1,
        ];
    }

    public function openText(): static
    {
        return $this->state(fn (): array => [
            'type' => QuestionType::OpenText,
        ]);
    }
}
