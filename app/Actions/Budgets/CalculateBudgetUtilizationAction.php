<?php

namespace App\Actions\Budgets;

use App\Enums\Frequency;
use App\Models\Budget;
use App\Models\Transaction;
use Illuminate\Support\Carbon;

class CalculateBudgetUtilizationAction
{
    /**
     * @return array{spent: float, remaining: float, percent: float, status: string}
     */
    public function execute(Budget $budget): array
    {
        [$start, $end] = $this->resolvePeriodRange($budget);

        $spent = (float) Transaction::where('team_id', $budget->team_id)
            ->where('category_id', $budget->category_id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->sum('base_amount');

        $amount = (float) $budget->amount;
        $remaining = round($amount - $spent, 2);
        $percent = $amount > 0 ? round(($spent / $amount) * 100, 1) : 0.0;

        $threshold = $budget->notification_threshold;
        $status = match (true) {
            $percent >= 100 => 'over',
            $percent >= $threshold => 'warning',
            default => 'ok',
        };

        return [
            'spent' => round($spent, 2),
            'remaining' => $remaining,
            'percent' => $percent,
            'status' => $status,
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolvePeriodRange(Budget $budget): array
    {
        $now = Carbon::now();

        return match ($budget->period) {
            Frequency::Monthly => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ],
            Frequency::Yearly => [
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear(),
            ],
            Frequency::Custom => [
                Carbon::parse($budget->start_date)->startOfDay(),
                $budget->end_date
                    ? Carbon::parse($budget->end_date)->endOfDay()
                    : $now->copy()->endOfDay(),
            ],
            default => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ],
        };
    }
}
