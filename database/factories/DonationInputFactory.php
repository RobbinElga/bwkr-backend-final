<?php

namespace Database\Factories;

use App\Enums\DonationSource;
use App\Enums\DonationStatus;
use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DonationInputFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ref_no'          => strtoupper(Str::random(8)),
            'donor_name'      => fake()->name(),
            'donor_phone'     => '08' . fake()->numerify('##########'),
            'donor_email'     => fake()->safeEmail(),
            'amount'          => fake()->numberBetween(50_000, 5_000_000),
            'source'          => DonationSource::Online->value,
            'status'          => DonationStatus::Pending->value,
            'bank_account_id' => BankAccount::factory(),
        ];
    }
}
