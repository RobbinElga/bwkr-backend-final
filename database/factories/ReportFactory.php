<?php

namespace Database\Factories;

use App\Enums\ReportCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ReportFactory extends Factory
{
    public function definition(): array
    {
        $title = 'Laporan ' . ucfirst(fake()->unique()->words(2, true));

        return [
            'title'        => $title,
            'slug'         => Str::slug($title) . '-' . Str::random(4),
            'category'     => ReportCategory::Annual->value,
            'year'         => fake()->numberBetween(2020, 2025),
            'description'  => fake()->paragraph(),
            'is_published' => true,
            'order'        => 0,
        ];
    }
}
