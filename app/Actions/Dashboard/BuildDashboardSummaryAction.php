<?php

namespace App\Actions\Dashboard;

use App\Actions\Budgets\CalculateBudgetUtilizationAction;
use App\Models\Account;
use App\Models\Budget;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Services\CurrencyConverterService;
use Illuminate\Support\Carbon;

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
            $rate = $this->converter->rateFor($account->currency, 'USD');
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
}
