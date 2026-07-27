<?php

namespace Database\Factories;

use App\Models\SavingGoal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavingGoal>
 */
class SavingGoalFactory extends Factory
{
    public function definition(): array
    {
        $target = fake()->randomFloat(2, 500, 50000);
        $current = fake()->randomFloat(2, 0, $target);

        return [
            'name' => fake()->words(3, true),
            'target_amount' => $target,
            'current_amount' => $current,
            'currency' => fake()->randomElement(['USD', 'EUR', 'IDR', 'GBP']),
            'deadline' => fake()->optional()->dateTimeBetween('+1 month', '+2 years'),
            'is_completed' => $current >= $target,
            'completed_at' => $current >= $target ? now() : null,
            'color' => fake()->hexColor(),
            'icon' => fake()->randomElement(['home', 'car', 'plane', 'education', 'shield']),
        ];
    }
}