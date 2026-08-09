<?php

namespace App\Jobs;

use App\Actions\Budgets\CalculateBudgetUtilizationAction;
use App\Models\Budget;
use App\Services\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendBudgetAlert implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->onQueue('notifications');
    }

    public function handle(TelegramBotService $telegram, CalculateBudgetUtilizationAction $utilizationAction): void
    {
        Budget::withoutGlobalScopes()
            ->with(['user.telegramUser', 'category'])
            ->get()
            ->each(function (Budget $budget) use ($telegram, $utilizationAction) {
                $telegramUser = $budget->user?->telegramUser;

                if (! $telegramUser || ! $telegramUser->is_active) {
                    return;
                }

                $utilization = $utilizationAction->execute($budget);

                if ($utilization['percent'] < $budget->notification_threshold) {
                    return;
                }

                $category = $budget->category?->name ?? 'Unknown';
                $spent = number_format((float) $utilization['spent'], 0, ',', '.');
                $amount = number_format((float) $budget->amount, 0, ',', '.');
                $percent = $utilization['percent'];

                $message = "⚠️ Budget {$category}: Rp{$spent}/Rp{$amount} ({$percent}%)";

                $telegram->sendMessage($telegramUser->chat_id, $message);
            });
    }
}
