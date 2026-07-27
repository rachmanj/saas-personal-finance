<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $incomeCategories = [
            ['name' => 'Salary', 'sort_order' => 1],
            ['name' => 'Freelance', 'sort_order' => 2],
            ['name' => 'Investment', 'sort_order' => 3],
            ['name' => 'Gift', 'sort_order' => 4],
        ];

        foreach ($incomeCategories as $category) {
            Category::withoutGlobalScope('team')->create([
                'team_id' => null,
                'name' => $category['name'],
                'type' => 'income',
                'sort_order' => $category['sort_order'],
                'is_system' => true,
                'is_active' => true,
            ]);
        }

        $expenseCategories = [
            ['name' => 'Food', 'sort_order' => 1],
            ['name' => 'Transport', 'sort_order' => 2],
            ['name' => 'Utilities', 'sort_order' => 3],
            ['name' => 'Entertainment', 'sort_order' => 4],
            ['name' => 'Health', 'sort_order' => 5],
            ['name' => 'Shopping', 'sort_order' => 6],
            ['name' => 'Education', 'sort_order' => 7],
            ['name' => 'Rent', 'sort_order' => 8],
            ['name' => 'Other', 'sort_order' => 9],
        ];

        foreach ($expenseCategories as $category) {
            Category::withoutGlobalScope('team')->create([
                'team_id' => null,
                'name' => $category['name'],
                'type' => 'expense',
                'sort_order' => $category['sort_order'],
                'is_system' => true,
                'is_active' => true,
            ]);
        }
    }
}
