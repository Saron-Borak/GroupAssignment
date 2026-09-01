<?php

namespace Database\Factories;

use App\Models\AddressType;
use App\Models\Student;
use App\Models\StudentAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StudentAddress> */
class StudentAddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'address_type_id' => AddressType::factory(),
            'line1' => fake()->buildingNumber().' '.fake()->streetName(),
            'line2' => null,
            'city' => fake()->city(),
            'province' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country' => 'Cambodia',
            'is_primary' => true,
        ];
    }
}
