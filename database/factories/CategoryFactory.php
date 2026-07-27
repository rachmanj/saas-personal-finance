<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $incomeNames = ['Salary', 'Freelance', 'Investment', 'Gift', 'Bonus'];
        $expenseNames = ['Food', 'Transport', 'Utilities', 'Entertainment', 'Health', 'Shopping', 'Education', 'Rent', 'Other'];

        $type = fake()->randomElement(['income', 'expense']);
        $name = $type === 'income'
            ? fake()->randomElement($incomeNames)
            : fake()->randomElement($expenseNames);

        return [
            'name' => $name,
            'type' => $type,
            'sort_order' => fake()->numberBetween(1, 20),
            'color' => fake()->hexColor(),
            'icon' => fake()->randomElement(['tag', 'shopping', 'car', 'home', 'heart', 'book']),
            'is_system' => false,
            'is_active' => true,
        ];
    }
}
