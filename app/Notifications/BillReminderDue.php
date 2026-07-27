<?php

namespace App\Notifications;

use App\Models\BillReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class BillReminderDue extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private BillReminder $billReminder) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', WebPushChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Bill Reminder: {$this->billReminder->name} due on {$this->billReminder->due_date->format('M j, Y')}")
            ->line("Your bill \"{$this->billReminder->name}\" for {$this->billReminder->currency} {$this->billReminder->amount} is due on {$this->billReminder->due_date->format('M j, Y')}.");
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Bill Reminder Due')
            ->body("{$this->billReminder->name} ({$this->billReminder->currency} {$this->billReminder->amount}) is due on {$this->billReminder->due_date->format('M j, Y')}")
            ->action('View', 'reminders');
    }
}
