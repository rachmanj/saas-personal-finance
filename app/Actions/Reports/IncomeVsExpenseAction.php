<?php

namespace App\Actions\Reports;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class IncomeVsExpenseAction
{
    /**
     * Aggregate income vs expense totals for a date range.
     *
     * @return array{total_income: string, total_expense: string, net: string}
     */
    public function execute(int $teamId, string $startDate, string $endDate): array
    {
        $result = Transaction::query()
            ->where('transactions.team_id', $teamId)
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->whereIn('transactions.type', ['income', 'expense'])
            ->select(
                'transactions.type',
                DB::raw('SUM(transactions.amount) as total')
            )
            ->groupBy('transactions.type')
            ->pluck('total', 'type');

        $totalIncome = (float) ($result['income'] ?? 0);
        $totalExpense = (float) ($result['expense'] ?? 0);

        return [
            'total_income' => number_format($totalIncome, 2, '.', ''),
            'total_expense' => number_format($totalExpense, 2, '.', ''),
            'net' => number_format($totalIncome - $totalExpense, 2, '.', ''),
        ];
    }
}