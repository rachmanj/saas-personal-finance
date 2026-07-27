<?php

namespace App\Actions\Reports;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TrendAction
{
    /**
     * Daily trend of total spending for a date range.
     *
     * @return array<int, array{date: string, total: string}>
     */
    public function execute(int $teamId, string $startDate, string $endDate): array
    {
        return Transaction::query()
            ->where('transactions.team_id', $teamId)
            ->where('transactions.type', 'expense')
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->select(
                'transactions.transaction_date as date',
                DB::raw('SUM(transactions.amount) as total')
            )
            ->groupBy('transactions.transaction_date')
            ->orderBy('transactions.transaction_date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'total' => number_format((float) $row->total, 2, '.', ''),
            ])
            ->toArray();
    }
}