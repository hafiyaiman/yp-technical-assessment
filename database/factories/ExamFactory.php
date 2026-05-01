<?php

namespace Database\Factories;

use App\Enums\ExamStatus;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Exam>
 */
class ExamFactory extends Factory
{
    public function definition(): array
    {
        $assignment = TeachingAssignment::factory()->create();

        return [
            'lecturer_id' => $assignment->lecturer_id,
            'teaching_assignment_id' => $assignment->id,
            'school_class_id' => $assignment->school_class_id,
            'subject_id' => $assignment->subject_id,
            'title' => fake()->sentence(3),
            'instructions' => fake()->paragraph(),
            'duration_minutes' => 15,
            'available_from' => now()->subDay(),
            'available_until' => now()->addWeek(),
            'status' => ExamStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => ExamStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (): array => [
            'status' => ExamStatus::Closed,
            'closed_at' => now(),
        ]);
    }

    public function forAssignment(TeachingAssignment $assignment): static
    {
        return $this->state(fn (): array => [
            'lecturer_id' => $assignment->lecturer_id,
            'teaching_assignment_id' => $assignment->id,
            'school_class_id' => $assignment->school_class_id,
            'subject_id' => $assignment->subject_id,
        ]);
    }
}
