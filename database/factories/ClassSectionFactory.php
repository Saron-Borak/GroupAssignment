<?php

namespace Database\Factories;

use App\Models\ClassSection;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ClassSection> */
class ClassSectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'lecturer_id' => User::factory()->faculty(),
            'term' => '2026 Semester 2',
            'section_code' => fake()->randomElement(['A', 'B', 'C']),
            'room' => fake()->randomElement(['A', 'B', 'C']).'-'.fake()->numberBetween(101, 405),
        ];
    }
}
