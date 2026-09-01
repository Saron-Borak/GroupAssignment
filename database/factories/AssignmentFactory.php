<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\ClassSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Assignment> */
class AssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'class_section_id' => ClassSection::factory(),
            'title' => ucfirst(fake()->words(3, true)),
            'description' => fake()->sentence(12),
            'deadline' => now()->addDays(fake()->numberBetween(-20, 20)),
        ];
    }
}
