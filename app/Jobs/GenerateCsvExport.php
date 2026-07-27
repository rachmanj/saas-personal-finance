<?php

namespace App\Jobs;

use App\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;

class GenerateCsvExport implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private int $userId,
        private int $teamId,
        private array $filters,
        public string $filename,
    ) {
        $this->onQueue('exports');
    }

    public function handle(): void
    {
        $transactions = $this->queryTransactions();

        $csv = Writer::createFromString();
        $csv->insertOne(['Date', 'Type', 'Description', 'Amount', 'Currency', 'Account', 'Category']);

        foreach ($transactions as $transaction) {
            $csv->insertOne([
                $transaction->transaction_date->format('Y-m-d'),
                $transaction->type->value ?? $transaction->type,
                $transaction->description,
                $transaction->amount,
                $transaction->currency,
                $transaction->account?->name,
                $transaction->category?->name,
            ]);
        }

        Storage::disk('exports')->put($this->filename, $csv->toString());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Transaction>
     */
    private function queryTransactions()
    {
        $query = Transaction::withoutGlobalScopes()
            ->where('team_id', $this->teamId)
            ->with(['account', 'category']);

        if (! empty($this->filters['start_date'])) {
            $query->where('transaction_date', '>=', $this->filters['start_date']);
        }

        if (! empty($this->filters['end_date'])) {
            $query->where('transaction_date', '<=', $this->filters['end_date']);
        }

        if (! empty($this->filters['account_ids'])) {
            $query->whereIn('account_id', $this->filters['account_ids']);
        }

        if (! empty($this->filters['category_ids'])) {
            $query->whereIn('category_id', $this->filters['category_ids']);
        }

        return $query->orderBy('transaction_date', 'desc')->get();
    }
}
