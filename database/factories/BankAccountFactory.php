<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BankAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'bank_name'      => fake()->randomElement(['BSI', 'Bank Muamalat', 'BCA', 'Mandiri']),
            'account_number' => fake()->numerify('##########'),
            'account_name'   => 'Yayasan Khulafaur Rasyidin',
            'is_active'      => true,
        ];
    }
}
