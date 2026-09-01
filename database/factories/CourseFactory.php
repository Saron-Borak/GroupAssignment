<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Course> */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'code' => strtoupper(fake()->unique()->lexify('??')).fake()->unique()->numberBetween(100, 499),
            'title' => ucwords(fake()->words(3, true)),
            'credit_hours' => fake()->randomElement([3, 3, 4]),
        ];
    }
}
