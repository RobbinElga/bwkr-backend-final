<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AchievementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'count'  => fake()->numberBetween(100, 10000),
            'label'  => 'Penerima Manfaat',
            'period' => '2025',
            'order'  => 0,
        ];
    }
}
