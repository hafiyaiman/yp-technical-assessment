<?php

namespace Database\Factories;

use App\Enums\ExamAttemptStatus;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExamAttempt>
 */
class ExamAttemptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory()->published(),
            'student_id' => User::factory()->student(),
            'status' => ExamAttemptStatus::InProgress,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'score' => 0,
            'max_score' => 0,
        ];
    }
}
