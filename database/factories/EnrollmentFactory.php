<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Models\ClassSection;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Enrollment> */
class EnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'class_section_id' => ClassSection::factory(),
            'student_id' => Student::factory(),
            'status' => EnrollmentStatus::Enrolled,
            'enrolled_at' => now()->subMonths(2)->toDateString(),
        ];
    }
}
