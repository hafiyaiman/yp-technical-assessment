<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'code' => Str::upper(Str::slug($name)),
            'description' => fake()->sentence(),
        ];
    }
}
