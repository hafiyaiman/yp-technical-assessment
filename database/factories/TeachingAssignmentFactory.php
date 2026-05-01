<?php

namespace Database\Factories;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeachingAssignment>
 */
class TeachingAssignmentFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(function ($assignment): void {
            $assignment->schoolClass->subjects()->syncWithoutDetaching($assignment->subject_id);
        });
    }

    public function definition(): array
    {
        return [
            'lecturer_id' => User::factory()->lecturer(),
            'school_class_id' => SchoolClass::factory(),
            'subject_id' => Subject::factory(),
        ];
    }
}
