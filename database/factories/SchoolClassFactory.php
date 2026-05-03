<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SchoolClass>
 */
class SchoolClassFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->bothify('Class ##?');

        return [
            'name' => $name,
            'code' => strtoupper(fake()->unique()->bothify('CLS-????????')),
            'description' => fake()->sentence(),
        ];
    }
}
