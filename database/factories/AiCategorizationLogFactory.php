<?php

namespace Database\Factories;

use App\Models\AiCategorizationLog;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiCategorizationLog>
 */
class AiCategorizationLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'predicted_category_id' => Category::factory(),
            'confidence' => fake()->randomFloat(3, 0.5, 1.0),
            'actual_category_id' => null,
            'was_correct' => null,
            'model_version' => 'stub-v1',
            'created_at' => now(),
        ];
    }
}
