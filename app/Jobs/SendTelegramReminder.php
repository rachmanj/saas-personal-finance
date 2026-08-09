<?php

namespace App\Jobs;

use App\Models\BillReminder;
use App\Services\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendTelegramReminder implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->onQueue('notifications');
    }

    public function handle(TelegramBotService $telegram): void
    {
        $today = today();

        BillReminder::withoutGlobalScopes()
            ->where('is_paid', false)
            ->get()
            ->filter(function (BillReminder $reminder) use ($today) {
                $daysUntilDue = $today->diffInDays($reminder->due_date, false);

                return in_array($daysUntilDue, $reminder->reminder_days_before ?? [], true);
            })
            ->each(function (BillReminder $reminder) use ($telegram) {
                $telegramUser = $reminder->user->telegramUser;

                if (! $telegramUser || ! $telegramUser->is_active) {
                    return;
                }

                $amount = number_format((float) $reminder->amount, 0, ',', '.');
                $dueDate = $reminder->due_date->format('d M Y');

                $message = "🔔 Pengingat: {$reminder->name} sebesar Rp{$amount} jatuh tempo tanggal {$dueDate}";

                $telegram->sendMessage($telegramUser->chat_id, $message);
            });
    }
}
