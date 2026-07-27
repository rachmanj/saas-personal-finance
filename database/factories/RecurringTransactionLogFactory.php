<?php

namespace Database\Factories;

use App\Models\RecurringTransaction;
use App\Models\RecurringTransactionLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringTransactionLog>
 */
class RecurringTransactionLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'recurring_transaction_id' => fn () => RecurringTransaction::factory(),
            'was_skipped' => false,
            'transaction_id' => null,
        ];
    }
}
