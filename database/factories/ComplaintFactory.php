<?php

namespace Database\Factories;

use App\Enums\ComplaintCategory;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Complaint> */
class ComplaintFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'reference' => 'CMP-'.fake()->unique()->numerify('#####'),
            'category' => fake()->randomElement(ComplaintCategory::cases()),
            'title' => ucfirst(fake()->words(4, true)),
            'description' => fake()->paragraph(),
            'status' => ComplaintStatus::Pending,
            'admin_response' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'status' => ComplaintStatus::Resolved,
            'admin_response' => 'Reviewed by the registry and resolved.',
            'resolved_at' => now()->subDays(2),
        ]);
    }
}
