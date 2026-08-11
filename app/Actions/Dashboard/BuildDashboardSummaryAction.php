<?php

namespace App\Actions\Dashboard;

use App\Actions\Budgets\CalculateBudgetUtilizationAction;
use App\Models\Account;
use App\Models\Budget;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Services\CurrencyConverterService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BuildDashboardSummaryAction
{
    public function __construct(
        private CurrencyConverterService $converter,
        private CalculateBudgetUtilizationAction $utilizationAction,
    ) {}

    public function execute(int $teamId): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $incomeTotal = $this->sumTransactionsByType($teamId, 'income', $startOfMonth, $endOfMonth);
        $expenseTotal = $this->sumTransactionsByType($teamId, 'expense', $startOfMonth, $endOfMonth);

        $netWorth = $this->calculateNetWorth($teamId);

        $last10Transactions = $this->getLast10Transactions($teamId);

        return [
            'income_total' => $incomeTotal,
            'expense_total' => $expenseTotal,
            'net_worth' => $netWorth,
            'last_10_transactions' => $last10Transactions,
            'budgets' => $this->getBudgetAlerts(),
            'upcoming_recurring' => $this->getUpcomingRecurring(),
            'saving_goals' => [],
            'category_expenses' => $this->getCategoryExpenses($teamId, $startOfMonth, $endOfMonth),
            'monthly_summary' => $this->getMonthlySummary($teamId),
            'net_worth_trend' => $this->getNetWorthTrend($teamId),
            'weekly_cashflow' => $this->getWeeklyCashflow($teamId),
        ];
    }

    private function sumTransactionsByType(int $teamId, string $type, Carbon $start, Carbon $end): float
    {
        return (float) Transaction::where('team_id', $teamId)
            ->where('type', $type)
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->sum('base_amount');
    }

    private function calculateNetWorth(int $teamId): float
    {
        $accounts = Account::where('team_id', $teamId)
            ->where('include_in_net_worth', true)
            ->get();

        $total = 0.0;
        foreach ($accounts as $account) {
            $rate = $this->converter->rateFor($account->currency, 'IDR');
            $total += (float) $account->balance * $rate;
        }

        return round($total, 2);
    }

    private function getLast10Transactions(int $teamId): array
    {
        return Transaction::where('team_id', $teamId)
            ->with(['account:id,name,currency', 'category:id,name,color,icon'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getBudgetAlerts(): array
    {
        return Budget::with('category')
            ->get()
            ->map(fn (Budget $budget) => [
                ...$budget->toArray(),
                'utilization' => $this->utilizationAction->execute($budget),
            ])
            ->filter(fn (array $budget) => in_array($budget['utilization']['status'], ['warning', 'over'], true))
            ->values()
            ->all();
    }

    private function getUpcomingRecurring(): array
    {
        return RecurringTransaction::where('is_active', true)
            ->where('next_due_date', '>=', today())
            ->where('next_due_date', '<=', now()->addDays(30))
            ->orderBy('next_due_date')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Expense breakdown by category for current month.
     * @return array<int, array{name: string, value: float, color: string|null}>
     */
    private function getCategoryExpenses(int $teamId, Carbon $start, Carbon $end): array
    {
        return Transaction::query()
            ->where('transactions.team_id', $teamId)
            ->where('transactions.type', 'expense')
            ->whereBetween('transactions.transaction_date', [$start->toDateString(), $end->toDateString()])
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->select(
                'categories.name',
                'categories.color',
                DB::raw('SUM(transactions.base_amount) as total')
            )
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'value' => round((float) $row->total, 2),
                'color' => $row->color,
            ])
            ->toArray();
    }

    /**
     * Income vs Expense for the last 6 months.
     * @return array<int, array{month: string, income: float, expense: float}>
     */
    private function getMonthlySummary(int $teamId): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i)->startOfMonth();
            $months[] = [
                'start' => $date->copy(),
                'end' => $date->copy()->endOfMonth(),
                'label' => $date->translatedFormat('M'),
            ];
        }

        $monthly = [];
        foreach ($months as $m) {
            $income = (float) Transaction::where('team_id', $teamId)
                ->where('type', 'income')
                ->whereBetween('transaction_date', [$m['start']->toDateString(), $m['end']->toDateString()])
                ->sum('base_amount');

            $expense = (float) Transaction::where('team_id', $teamId)
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$m['start']->toDateString(), $m['end']->toDateString()])
                ->sum('base_amount');

            $monthly[] = [
                'month' => $m['label'],
                'income' => round($income, 2),
                'expense' => round($expense, 2),
            ];
        }

        return $monthly;
    }

    /**
     * Cumulative net worth trend over the last 12 months.
     * Uses cumulative (income - expense) from transactions as a proxy for net worth growth.
     * @return array<int, array{month: string, net_worth: float}>
     */
    private function getNetWorthTrend(int $teamId): array
    {
        // Get all transactions for the last 12 months, grouped by month
        $results = Transaction::query()
            ->where('team_id', $teamId)
            ->whereIn('type', ['income', 'expense'])
            ->where('transaction_date', '>=', Carbon::now()->subMonths(12)->startOfMonth()->toDateString())
            ->select(
                DB::raw("DATE_FORMAT(transaction_date, '%Y-%m') as month_key"),
                'type',
                DB::raw('SUM(base_amount) as total')
            )
            ->groupBy('month_key', 'type')
            ->orderBy('month_key')
            ->get();

        // Build month-by-month net with running total
        $trend = [];
        $runningTotal = 0.0;
        $monthMap = [];

        foreach ($results as $row) {
            $key = $row->month_key;
            if (! isset($monthMap[$key])) {
                $monthMap[$key] = ['income' => 0.0, 'expense' => 0.0];
            }
            if ($row->type === 'income') {
                $monthMap[$key]['income'] = (float) $row->total;
            } else {
                $monthMap[$key]['expense'] = (float) $row->total;
            }
        }

        // Generate all 12 months
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i)->startOfMonth();
            $key = $date->format('Y-m');
            $label = $date->translatedFormat('M');

            $monthNet = ($monthMap[$key]['income'] ?? 0.0) - ($monthMap[$key]['expense'] ?? 0.0);
            $runningTotal += $monthNet;

            $trend[] = [
                'month' => $label,
                'net_worth' => round($runningTotal, 2),
            ];
        }

        return $trend;
    }

    /**
     * Weekly cashflow summary for the current month.
     * @return array<int, array{week: string, income: float, expense: float, net: float}>
     */
    private function getWeeklyCashflow(int $teamId): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $weeks = [];
        $weekStart = $startOfMonth->copy();

        $weekNum = 1;
        while ($weekStart->lte($endOfMonth)) {
            $weekEnd = $weekStart->copy()->addDays(6);
            if ($weekEnd->gt($endOfMonth)) {
                $weekEnd = $endOfMonth->copy();
            }

            $income = (float) Transaction::where('team_id', $teamId)
                ->where('type', 'income')
                ->whereBetween('transaction_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->sum('base_amount');

            $expense = (float) Transaction::where('team_id', $teamId)
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->sum('base_amount');

            $weeks[] = [
                'week' => "W{$weekNum}",
                'income' => round($income, 2),
                'expense' => round($expense, 2),
                'net' => round($income - $expense, 2),
            ];

            $weekStart->addWeek();
            $weekNum++;
        }

        return $weeks;
    }
}
