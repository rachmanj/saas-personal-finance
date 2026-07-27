<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(TransactionType::cases());

        return [
            'type' => $type->value,
            'amount' => fake()->randomFloat(2, 1, 10000),
            'currency' => fake()->randomElement(['USD', 'EUR', 'IDR', 'GBP']),
            'base_amount' => fake()->randomFloat(2, 1, 10000),
            'base_currency' => 'USD',
            'exchange_rate' => 1.000000,
            'description' => fake()->sentence(3),
            'notes' => fake()->optional()->sentence(),
            'transaction_date' => fake()->date(),
            'is_reconciled' => fake()->boolean(30),
            'source' => 'manual',
        ];
    }
}
