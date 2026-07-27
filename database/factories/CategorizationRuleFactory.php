<?php

namespace Database\Factories;

use App\Enums\CategorizationRuleSource;
use App\Models\CategorizationRule;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategorizationRule>
 */
class CategorizationRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pattern' => fake()->unique()->word(),
            'confidence' => fake()->randomFloat(3, 0.5, 1.0),
            'source' => CategorizationRuleSource::Manual,
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
        ];
    }
}
