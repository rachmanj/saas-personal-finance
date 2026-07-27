<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\RecurringTransaction;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringTransaction>
 */
class RecurringTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => fn () => Team::factory(),
            'user_id' => fn () => User::factory(),
            'account_id' => fn () => Account::factory(),
            'category_id' => null,
            'type' => 'expense',
            'amount' => fake()->randomFloat(2, 10, 1000),
            'currency' => 'USD',
            'description' => fake()->sentence(3),
            'frequency' => 'monthly',
            'interval' => 1,
            'start_date' => now()->toDateString(),
            'next_due_date' => now()->toDateString(),
            'is_active' => true,
            'template_type' => 'bill',
        ];
    }
}
