<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TestimonialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'       => fake()->name(),
            'title'      => 'Tokoh Masyarakat',
            'content'    => fake()->paragraph(),
            'is_visible' => true,
            'order'      => 0,
        ];
    }
}
