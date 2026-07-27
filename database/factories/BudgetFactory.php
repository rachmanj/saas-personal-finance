<?php

namespace Database\Factories;

use App\Models\Budget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'amount' => fake()->randomFloat(2, 100, 5000),
            'currency' => 'USD',
            'period' => fake()->randomElement(['monthly', 'yearly', 'custom']),
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => null,
            'rollover' => false,
            'notification_threshold' => 80,
        ];
    }
}
