<?php

namespace Database\Factories;

use App\Enums\ProgramLevel;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Program> */
class ProgramFactory extends Factory
{
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'code' => strtoupper(fake()->unique()->lexify('P???')),
            'name' => 'BSc '.fake()->words(2, true),
            'level' => ProgramLevel::Bachelor,
            'duration_years' => 4,
        ];
    }
}
