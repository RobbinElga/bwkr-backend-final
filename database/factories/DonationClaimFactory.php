<?php

namespace Database\Factories;

use App\Enums\ClaimStatus;
use App\Models\DonationInput;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class DonationClaimFactory extends Factory
{
    public function definition(): array
    {
        return [
            'donation_input_id' => DonationInput::factory(),
            'project_id'        => Project::factory(),
            'amount'            => fake()->numberBetween(50_000, 1_000_000),
            'status'            => ClaimStatus::Pending->value,
        ];
    }
}
