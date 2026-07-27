<?php

namespace App\Actions\Reports;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class YearOverYearAction
{
    /**
     * Compare current year vs previous year by month.
     *
     * @return array{current_year: array<int, string>, previous_year: array<int, string>, months: array<int, string>}
     */
    public function execute(int $teamId, int $year): array
    {
        $results = Transaction::query()
            ->where('transactions.team_id', $teamId)
            ->where('transactions.type', 'expense')
            ->whereYear('transactions.transaction_date', '>=', $year - 1)
            ->whereYear('transactions.transaction_date', '<=', $year)
            ->select(
                DB::raw('YEAR(transactions.transaction_date) as year'),
                DB::raw('MONTH(transactions.transaction_date) as month'),
                DB::raw('SUM(transactions.amount) as total')
            )
            ->groupBy('year', 'month')
            ->get();

        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
        ];

        $currentYear = [];
        $previousYear = [];
        $months = [];

        for ($month = 1; $month <= 12; $month++) {
            $months[] = $monthNames[$month];
            $currTotal = '0.00';
            $prevTotal = '0.00';

            foreach ($results as $row) {
                if ((int) $row->month === $month) {
                    if ((int) $row->year === $year) {
                        $currTotal = number_format((float) $row->total, 2, '.', '');
                    } elseif ((int) $row->year === $year - 1) {
                        $prevTotal = number_format((float) $row->total, 2, '.', '');
                    }
                }
            }

            $currentYear[] = $currTotal;
            $previousYear[] = $prevTotal;
        }

        return [
            'current_year' => $currentYear,
            'previous_year' => $previousYear,
            'months' => $months,
        ];
    }
}