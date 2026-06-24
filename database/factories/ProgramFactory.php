<?php

namespace Database\Factories;

use App\Enums\ProgramStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProgramFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(3, true));

        return [
            'name'        => $name,
            'slug'        => Str::slug($name) . '-' . Str::random(4),
            'description' => fake()->paragraph(),
            'status'      => ProgramStatus::Active->value,
            'order'       => 0,
        ];
    }
}
