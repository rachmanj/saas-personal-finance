<?php

namespace App\Actions\Recurring;

use Illuminate\Support\Carbon;

class CalculateNextDueDateAction
{
    public function execute(string $frequency, int $interval, Carbon $fromDate): Carbon
    {
        return match ($frequency) {
            'daily' => $fromDate->copy()->addDays($interval),
            'weekly' => $fromDate->copy()->addWeeks($interval),
            'monthly' => $fromDate->copy()->addMonths($interval),
            'yearly' => $fromDate->copy()->addYears($interval),
            'custom' => $fromDate->copy()->addDays($interval),
            default => $fromDate->copy()->addMonth(),
        };
    }
}
