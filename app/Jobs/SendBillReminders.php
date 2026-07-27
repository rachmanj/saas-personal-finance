<?php

namespace App\Jobs;

use App\Models\BillReminder;
use App\Notifications\BillReminderDue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendBillReminders implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $today = today();

        BillReminder::withoutGlobalScopes()
            ->where('is_paid', false)
            ->get()
            ->filter(function (BillReminder $reminder) use ($today) {
                $daysUntilDue = $today->diffInDays($reminder->due_date, false);

                return in_array($daysUntilDue, $reminder->reminder_days_before ?? [], true);
            })
            ->each(function (BillReminder $reminder) {
                $reminder->user->notify(new BillReminderDue($reminder));
            });
    }
}
