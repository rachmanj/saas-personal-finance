<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AutoCategorizeImport implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int>  $transactionIds
     */
    public function __construct(
        public Import $import,
        public array $transactionIds = []
    ) {
        $this->onQueue('imports');
    }

    public function handle(): void
    {
        if (empty($this->transactionIds)) {
            return;
        }

        $transactions = Transaction::query()
            ->whereIn('id', $this->transactionIds)
            ->whereNull('category_id')
            ->get();

        foreach ($transactions as $transaction) {
            if (! $transaction->description) {
                continue;
            }

            $existing = Transaction::query()
                ->whereNotNull('category_id')
                ->where('id', '!=', $transaction->id)
                ->where('description', 'like', '%'.$transaction->description.'%')
                ->select('category_id')
                ->get();

            if ($existing->isEmpty()) {
                continue;
            }

            $categoryId = $existing->countBy('category_id')->sortDesc()->keys()->first();

            if ($categoryId) {
                $transaction->update(['category_id' => $categoryId]);
            }
        }
    }
}
