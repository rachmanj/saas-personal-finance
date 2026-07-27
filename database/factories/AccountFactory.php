<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    public function definition(): array
    {
        $accounts = [
            'Checking Account' => 'checking',
            'Savings Account' => 'savings',
            'Credit Card' => 'credit_card',
            'Cash Wallet' => 'cash',
            'Investment Account' => 'investment',
        ];

        $name = fake()->randomElement(array_keys($accounts));

        return [
            'name' => $name,
            'type' => $accounts[$name],
            'currency' => fake()->randomElement(['USD', 'EUR', 'IDR', 'GBP']),
            'balance' => fake()->randomFloat(2, 0, 100000),
            'initial_balance' => fake()->randomFloat(2, 0, 50000),
            'include_in_net_worth' => fake()->boolean(80),
            'is_active' => fake()->boolean(90),
            'color' => fake()->hexColor(),
            'icon' => fake()->randomElement(['bank', 'wallet', 'credit-card', 'cash', 'piggy-bank']),
        ];
    }
}
