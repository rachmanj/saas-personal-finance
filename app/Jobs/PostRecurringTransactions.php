<?php

namespace App\Jobs;

use App\Actions\Recurring\PostRecurringTransactionAction;
use App\Models\RecurringTransaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PostRecurringTransactions implements ShouldQueue
{
    use Queueable;

    public function handle(PostRecurringTransactionAction $action): void
    {
        RecurringTransaction::where('is_active', true)
            ->where('next_due_date', '<=', today())
            ->chunk(100, function ($rows) use ($action) {
                foreach ($rows as $row) {
                    $action->execute($row);
                }
            });
    }
}
