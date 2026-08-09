<?php

namespace Tests\Feature\Telegram;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Jobs\SendBudgetAlert;
use App\Jobs\SendTelegramReminder;
use App\Models\BillReminder;
use App\Models\Budget;
use App\Models\Category;
use App\Models\TelegramUser;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReminderTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected TelegramUser $telegramUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->user = User::factory()->create();
        (new CreatePersonalTeamAction)->execute($this->user);

        $this->telegramUser = TelegramUser::create([
            'user_id' => $this->user->id,
            'chat_id' => 123456789,
            'username' => 'testuser',
            'first_name' => 'Test',
            'is_active' => true,
            'linked_at' => now(),
        ]);

        config([
            'services.telegram.bot_token' => '123456:ABC-DEF1234ghikl-zyx57W2v1u123ew11',
        ]);

        Http::fake();
    }

    // ─── SendTelegramReminder tests ───────────────────────────────────────

    public function test_telegram_reminder_sends_correct_message(): void
    {
        $reminder = BillReminder::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'name' => 'Listrik',
            'amount' => 350000,
            'currency' => 'IDR',
            'due_date' => today()->addDays(3),
            'reminder_days_before' => [3],
            'is_paid' => false,
        ]);

        $job = new SendTelegramReminder;
        $job->handle(app(\App\Services\TelegramBotService::class));

        $amount = number_format(350000, 0, ',', '.');
        $dueDate = today()->addDays(3)->format('d M Y');

        Http::assertSent(function ($request) use ($amount, $dueDate) {
            return $request->url() === 'https://api.telegram.org/bot123456:ABC-DEF1234ghikl-zyx57W2v1u123ew11/sendMessage'
                && $request['chat_id'] === '123456789'
                && str_contains($request['text'], '🔔 Pengingat: Listrik')
                && str_contains($request['text'], "Rp{$amount}")
                && str_contains($request['text'], $dueDate);
        });
    }

    public function test_telegram_reminder_skips_paid_bills(): void
    {
        BillReminder::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'name' => 'Listrik',
            'amount' => 350000,
            'currency' => 'IDR',
            'due_date' => today()->addDays(3),
            'reminder_days_before' => [3],
            'is_paid' => true,
        ]);

        $job = new SendTelegramReminder;
        $job->handle(app(\App\Services\TelegramBotService::class));

        Http::assertNothingSent();
    }

    public function test_telegram_reminder_skips_when_days_dont_match(): void
    {
        BillReminder::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'name' => 'Listrik',
            'amount' => 350000,
            'currency' => 'IDR',
            'due_date' => today()->addDays(3),
            'reminder_days_before' => [7], // Only reminds 7 days before, not 3
            'is_paid' => false,
        ]);

        $job = new SendTelegramReminder;
        $job->handle(app(\App\Services\TelegramBotService::class));

        Http::assertNothingSent();
    }

    public function test_telegram_reminder_skips_inactive_telegram_user(): void
    {
        $this->telegramUser->update(['is_active' => false]);

        BillReminder::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'name' => 'Listrik',
            'amount' => 350000,
            'currency' => 'IDR',
            'due_date' => today()->addDays(3),
            'reminder_days_before' => [3],
            'is_paid' => false,
        ]);

        $job = new SendTelegramReminder;
        $job->handle(app(\App\Services\TelegramBotService::class));

        Http::assertNothingSent();
    }

    public function test_telegram_reminder_skips_user_without_telegram(): void
    {
        $userWithoutTelegram = User::factory()->create();
        (new CreatePersonalTeamAction)->execute($userWithoutTelegram);

        BillReminder::factory()->create([
            'team_id' => $userWithoutTelegram->current_team_id,
            'user_id' => $userWithoutTelegram->id,
            'name' => 'Listrik',
            'amount' => 350000,
            'currency' => 'IDR',
            'due_date' => today()->addDays(3),
            'reminder_days_before' => [3],
            'is_paid' => false,
        ]);

        $job = new SendTelegramReminder;
        $job->handle(app(\App\Services\TelegramBotService::class));

        Http::assertNothingSent();
    }

    // ─── SendBudgetAlert tests ────────────────────────────────────────────

    public function test_budget_alert_sends_when_threshold_exceeded(): void
    {
        $category = Category::factory()->create([
            'team_id' => $this->user->current_team_id,
            'name' => 'Makanan',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $budget = Budget::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'amount' => 1000000,
            'currency' => 'IDR',
            'period' => 'monthly',
            'start_date' => today()->startOfMonth(),
            'notification_threshold' => 80,
        ]);

        // Create transactions that put spending at 85%
        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'category_id' => $category->id,
            'type' => 'expense',
            'base_amount' => 850000,
            'transaction_date' => today()->toDateString(),
        ]);

        $job = new SendBudgetAlert;
        $job->handle(
            app(\App\Services\TelegramBotService::class),
            app(\App\Actions\Budgets\CalculateBudgetUtilizationAction::class)
        );

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.telegram.org/bot123456:ABC-DEF1234ghikl-zyx57W2v1u123ew11/sendMessage'
                && $request['chat_id'] === '123456789'
                && str_contains($request['text'], '⚠️ Budget Makanan')
                && str_contains($request['text'], 'Rp850.000')
                && str_contains($request['text'], 'Rp1.000.000')
                && str_contains($request['text'], '(85%)');
        });
    }

    public function test_budget_alert_skips_when_below_threshold(): void
    {
        $category = Category::factory()->create([
            'team_id' => $this->user->current_team_id,
            'name' => 'Makanan',
            'type' => 'expense',
            'is_active' => true,
        ]);

        Budget::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'amount' => 1000000,
            'currency' => 'IDR',
            'period' => 'monthly',
            'start_date' => today()->startOfMonth(),
            'notification_threshold' => 80,
        ]);

        // Only 50% spent — below threshold
        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'category_id' => $category->id,
            'type' => 'expense',
            'base_amount' => 500000,
            'transaction_date' => today()->toDateString(),
        ]);

        $job = new SendBudgetAlert;
        $job->handle(
            app(\App\Services\TelegramBotService::class),
            app(\App\Actions\Budgets\CalculateBudgetUtilizationAction::class)
        );

        Http::assertNothingSent();
    }

    public function test_budget_alert_skips_inactive_telegram_user(): void
    {
        $this->telegramUser->update(['is_active' => false]);

        $category = Category::factory()->create([
            'team_id' => $this->user->current_team_id,
            'name' => 'Makanan',
            'type' => 'expense',
            'is_active' => true,
        ]);

        Budget::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'amount' => 1000000,
            'currency' => 'IDR',
            'period' => 'monthly',
            'start_date' => today()->startOfMonth(),
            'notification_threshold' => 80,
        ]);

        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'category_id' => $category->id,
            'type' => 'expense',
            'base_amount' => 850000,
            'transaction_date' => today()->toDateString(),
        ]);

        $job = new SendBudgetAlert;
        $job->handle(
            app(\App\Services\TelegramBotService::class),
            app(\App\Actions\Budgets\CalculateBudgetUtilizationAction::class)
        );

        Http::assertNothingSent();
    }

    public function test_budget_alert_sends_for_100_percent_utilization(): void
    {
        $category = Category::factory()->create([
            'team_id' => $this->user->current_team_id,
            'name' => 'Transport',
            'type' => 'expense',
            'is_active' => true,
        ]);

        Budget::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'amount' => 500000,
            'currency' => 'IDR',
            'period' => 'monthly',
            'start_date' => today()->startOfMonth(),
            'notification_threshold' => 80,
        ]);

        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'category_id' => $category->id,
            'type' => 'expense',
            'base_amount' => 500000,
            'transaction_date' => today()->toDateString(),
        ]);

        $job = new SendBudgetAlert;
        $job->handle(
            app(\App\Services\TelegramBotService::class),
            app(\App\Actions\Budgets\CalculateBudgetUtilizationAction::class)
        );

        Http::assertSent(function ($request) {
            return str_contains($request['text'], '(100%)');
        });
    }
}
