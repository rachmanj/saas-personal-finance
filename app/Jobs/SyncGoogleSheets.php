<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\GoogleSheetsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncGoogleSheets implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private int $userId,
        private int $teamId,
        private string $spreadsheetId,
        private array $filters,
        public string $jobId,
    ) {
        $this->onQueue('exports');
    }

    public function handle(GoogleSheetsService $googleSheetsService): void
    {
        $transactions = Transaction::withoutGlobalScopes()
            ->where('team_id', $this->teamId)
            ->with(['account', 'category'])
            ->when(! empty($this->filters['start_date']), fn ($q) => $q->where('transaction_date', '>=', $this->filters['start_date']))
            ->when(! empty($this->filters['end_date']), fn ($q) => $q->where('transaction_date', '<=', $this->filters['end_date']))
            ->when(! empty($this->filters['account_ids']), fn ($q) => $q->whereIn('account_id', $this->filters['account_ids']))
            ->when(! empty($this->filters['category_ids']), fn ($q) => $q->whereIn('category_id', $this->filters['category_ids']))
            ->orderBy('transaction_date', 'desc')
            ->get()
            ->map(fn ($t) => [
                'date' => $t->transaction_date->format('Y-m-d'),
                'type' => $t->type->value ?? $t->type,
                'description' => $t->description,
                'amount' => $t->amount,
                'currency' => $t->currency,
            ])
            ->all();

        $googleSheetsService->syncTransactions($this->spreadsheetId, $transactions);
    }
}
