<?php

namespace Database\Factories;

use App\Models\BillReminder;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillReminder>
 */
class BillReminderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => fn () => Team::factory(),
            'user_id' => fn () => User::factory(),
            'name' => fake()->words(2, true),
            'amount' => fake()->randomFloat(2, 20, 500),
            'currency' => 'USD',
            'due_date' => now()->addDays(fake()->numberBetween(1, 30))->toDateString(),
            'reminder_days_before' => [1, 3, 7],
            'is_recurring' => false,
            'frequency' => null,
            'is_paid' => false,
            'paid_at' => null,
            'subscription_slug' => null,
        ];
    }
}
