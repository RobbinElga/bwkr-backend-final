<?php

namespace Database\Factories;

use App\Enums\NewsStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NewsFactory extends Factory
{
    public function definition(): array
    {
        $title = ucfirst(fake()->unique()->words(3, true));

        return [
            'title'        => $title,
            'slug'         => Str::slug($title) . '-' . Str::random(4),
            'content'      => '<p>' . fake()->paragraph() . '</p>',
            'author'       => fake()->name(),
            'category'     => 'Umum',
            'tags'         => ['wakaf'],
            'status'       => NewsStatus::Published->value,
            'published_at' => now(),
        ];
    }
}
