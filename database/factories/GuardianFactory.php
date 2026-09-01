<?php

namespace Database\Factories;

use App\Enums\GuardianRelationship;
use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Guardian> */
class GuardianFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'full_name' => fake()->name(),
            'relationship' => fake()->randomElement(GuardianRelationship::cases()),
            'phone' => fake()->numerify('01# ### ###'),
            'email' => fake()->optional()->safeEmail(),
            'occupation' => fake()->optional()->jobTitle(),
            'is_emergency_contact' => true,
        ];
    }
}
