<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PartnerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'       => fake()->company(),
            'type'       => 'Sponsor',
            'pic_name'   => fake()->name(),
            'pic_phone'  => '0812' . fake()->numerify('########'),
            'pic_email'  => fake()->safeEmail(),
            'is_visible' => true,
        ];
    }
}
