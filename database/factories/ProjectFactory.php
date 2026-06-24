<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(3, true));

        return [
            'program_id'    => Program::factory(),
            'name'          => $name,
            'slug'          => Str::slug($name) . '-' . Str::random(4),
            'description'   => fake()->paragraph(),
            'target_amount' => fake()->numberBetween(1_000_000, 100_000_000),
            'status'        => ProjectStatus::Running->value,
            'start_date'    => now(),
            'end_date'      => now()->addMonths(3),
        ];
    }
}
