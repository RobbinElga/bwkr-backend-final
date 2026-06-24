<?php

namespace Database\Factories;

use App\Enums\ExpenseStatus;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'amount'     => fake()->numberBetween(100_000, 2_000_000),
            'status'     => ExpenseStatus::Pending->value,
        ];
    }
}
