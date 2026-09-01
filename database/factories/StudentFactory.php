<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\StudentStatus;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Student> */
class StudentFactory extends Factory
{
    public function definition(): array
    {
        $intake = fake()->numberBetween(2023, 2026);
        $first = fake()->firstName();
        $last = fake()->lastName();

        return [
            'user_id' => null,
            'program_id' => Program::factory(),
            'student_id_no' => 'EAMU-'.$intake.'-'.fake()->unique()->numerify('####'),
            'first_name' => $first,
            'last_name' => $last,
            'gender' => fake()->randomElement(Gender::cases()),
            'date_of_birth' => fake()->dateTimeBetween('-28 years', '-18 years')->format('Y-m-d'),
            'nationality' => 'Cambodian',
            'national_id' => fake()->unique()->numerify('##########'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('01# ### ###'),
            'photo_path' => null,
            'intake_year' => $intake,
            'admission_date' => $intake.'-09-01',
            'status' => StudentStatus::Active,
        ];
    }

    public function graduated(): static
    {
        return $this->state(fn () => ['status' => StudentStatus::Graduated]);
    }
}
