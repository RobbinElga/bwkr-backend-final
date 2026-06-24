<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ImpactVideoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'caption'     => fake()->sentence(),
            'order'       => 0,
        ];
    }
}
