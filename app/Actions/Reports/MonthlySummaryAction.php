<?php

namespace App\Actions\Reports;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class MonthlySummaryAction
{
    /**
     * Aggregate income and expense per month for a given year.
     *
     * @return array<int, array{month: int, month_name: string, income: string, expense: string, net: string}>
     */
    public function execute(int $teamId, int $year): array
    {
        $results = Transaction::query()
            ->where('transactions.team_id', $teamId)
            ->whereYear('transactions.transaction_date', $year)
            ->whereIn('transactions.type', ['income', 'expense'])
            ->select(
                DB::raw('MONTH(transactions.transaction_date) as month'),
                'transactions.type',
                DB::raw('SUM(transactions.amount) as total')
            )
            ->groupBy('month', 'transactions.type')
            ->get();

        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        $monthly = [];
        for ($month = 1; $month <= 12; $month++) {
            $income = 0.0;
            $expense = 0.0;

            foreach ($results as $row) {
                if ((int) $row->month === $month) {
                    if ($row->type === 'income') {
                        $income = (float) $row->total;
                    } else {
                        $expense = (float) $row->total;
                    }
                }
            }

            $monthly[] = [
                'month' => $month,
                'month_name' => $monthNames[$month],
                'income' => number_format($income, 2, '.', ''),
                'expense' => number_format($expense, 2, '.', ''),
                'net' => number_format($income - $expense, 2, '.', ''),
            ];
        }

        return $monthly;
    }
}