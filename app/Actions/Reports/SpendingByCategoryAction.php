<?php

namespace App\Actions\Reports;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class SpendingByCategoryAction
{
    /**
     * Aggregate spending by category for a date range.
     *
     * @return array<int, array{name: string, total: string, count: int, color: string|null}>
     */
    public function execute(int $teamId, string $startDate, string $endDate): array
    {
        return Transaction::query()
            ->where('transactions.team_id', $teamId)
            ->where('transactions.type', 'expense')
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->select(
                'categories.name',
                'categories.color',
                DB::raw('SUM(transactions.amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'total' => number_format((float) $row->total, 2, '.', ''),
                'count' => (int) $row->count,
                'color' => $row->color,
            ])
            ->toArray();
    }
}