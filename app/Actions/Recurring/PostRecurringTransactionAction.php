<?php

namespace App\Actions\Recurring;

use App\Actions\Transactions\CreateTransactionAction;
use App\Models\RecurringTransaction;
use App\Models\RecurringTransactionLog;

class PostRecurringTransactionAction
{
    public function __construct(
        private CreateTransactionAction $createTransactionAction,
        private CalculateNextDueDateAction $calculateNextDueDateAction,
    ) {}

    public function execute(RecurringTransaction $recurring): void
    {
        $transaction = $this->createTransactionAction->execute([
            'team_id' => $recurring->team_id,
            'user_id' => $recurring->user_id,
            'account_id' => $recurring->account_id,
            'category_id' => $recurring->category_id,
            'type' => $recurring->type,
            'amount' => $recurring->amount,
            'currency' => $recurring->currency,
            'description' => $recurring->description,
            'transaction_date' => now()->toDateString(),
            'source' => 'recurring',
        ]);

        RecurringTransactionLog::create([
            'recurring_transaction_id' => $recurring->id,
            'transaction_id' => $transaction->id,
            'posted_at' => now(),
            'was_skipped' => false,
        ]);

        $recurring->update([
            'last_posted_date' => now()->toDateString(),
            'next_due_date' => $this->calculateNextDueDateAction->execute(
                $recurring->frequency,
                $recurring->interval,
                $recurring->next_due_date,
            ),
        ]);
    }
}
