<?php

namespace App\Jobs;

use App\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BatchAutoCategorize implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $teamId) {}

    public function handle(): void
    {
        Transaction::withoutGlobalScopes()
            ->where('team_id', $this->teamId)
            ->whereNull('category_id')
            ->chunkById(100, function ($transactions) {
                foreach ($transactions as $transaction) {
                    AutoCategorizeTransaction::dispatch($transaction);
                }
            });
    }
}
