<?php

namespace Database\Factories;

use App\Models\AddressType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AddressType> */
class AddressTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('AT??')),
            'name' => ucfirst(fake()->unique()->word()),
        ];
    }
}
