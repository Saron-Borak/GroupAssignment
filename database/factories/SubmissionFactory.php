<?php

namespace Database\Factories;

use App\Enums\SubmissionStatus;
use App\Models\Assignment;
use App\Models\Student;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Submission> */
class SubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'assignment_id' => Assignment::factory(),
            'student_id' => Student::factory(),
            'file_path' => 'submissions/'.fake()->uuid().'.pdf',
            'original_filename' => fake()->word().'.pdf',
            'submitted_at' => now()->subDays(fake()->numberBetween(1, 20)),
            'status' => SubmissionStatus::OnTime,
        ];
    }

    public function late(): static
    {
        return $this->state(fn () => ['status' => SubmissionStatus::Late]);
    }
}
