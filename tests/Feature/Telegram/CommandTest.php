<?php

namespace Tests\Feature\Telegram;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Actions\Telegram\ProcessMessageAction;
use App\Enums\Frequency;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\TelegramUser;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommandTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Account $account;
    protected TelegramUser $telegramUser;
    protected ProcessMessageAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->user = User::factory()->create();
        (new CreatePersonalTeamAction)->execute($this->user);

        $this->account = Account::factory()->create([
            'team_id' => $this->user->current_team_id,
            'name' => 'Dompet Utama',
            'currency' => 'IDR',
            'balance' => 5_000_000,
            'is_active' => true,
        ]);

        $this->telegramUser = TelegramUser::create([
            'user_id' => $this->user->id,
            'chat_id' => 123456789,
            'username' => 'testuser',
            'first_name' => 'Test',
            'is_active' => true,
            'linked_at' => now(),
        ]);

        $this->actingAs($this->user);
        $this->action = new ProcessMessageAction;
    }

    protected function makeUpdate(string $text, array $overrides = []): array
    {
        return array_merge([
            'update_id' => 12345,
            'message' => [
                'message_id' => 1,
                'chat' => [
                    'id' => 123456789,
                    'type' => 'private',
                ],
                'from' => [
                    'id' => 987654321,
                    'is_bot' => false,
                    'first_name' => 'Test',
                    'username' => 'testuser',
                ],
                'date' => time(),
                'text' => $text,
            ],
        ], $overrides);
    }

    // ============================================================
    // /balance
    // ============================================================

    public function test_balance_command_shows_all_active_accounts(): void
    {
        Account::factory()->create([
            'team_id' => $this->user->current_team_id,
            'name' => 'Tabungan',
            'currency' => 'IDR',
            'balance' => 2_500_000,
            'is_active' => true,
        ]);

        // Inactive account — should NOT appear
        Account::factory()->create([
            'team_id' => $this->user->current_team_id,
            'name' => 'Rekening Lama',
            'currency' => 'IDR',
            'balance' => 100_000,
            'is_active' => false,
        ]);

        $response = $this->action->handle($this->makeUpdate('/balance'));

        $this->assertStringContainsString('💰', $response['text']);
        $this->assertStringContainsString('Dompet Utama', $response['text']);
        $this->assertStringContainsString('Tabungan', $response['text']);
        $this->assertStringContainsString('5.000.000', $response['text']);
        $this->assertStringContainsString('2.500.000', $response['text']);
        $this->assertStringNotContainsString('Rekening Lama', $response['text']);
        $this->assertEquals('HTML', $response['parse_mode']);
    }

    public function test_balance_command_shows_empty_state_when_no_accounts(): void
    {
        // Delete the existing account
        $this->account->delete();

        // Create only inactive accounts
        Account::factory()->create([
            'team_id' => $this->user->current_team_id,
            'name' => 'Nonaktif',
            'currency' => 'IDR',
            'balance' => 1_000,
            'is_active' => false,
        ]);

        $response = $this->action->handle($this->makeUpdate('/balance'));

        $this->assertStringContainsString('belum ada', strtolower($response['text']));
    }

    // ============================================================
    // /today
    // ============================================================

    public function test_today_command_shows_todays_transactions_with_totals(): void
    {
        $today = now()->toDateString();

        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'type' => 'expense',
            'amount' => 50000,
            'base_amount' => 50000,
            'currency' => 'IDR',
            'description' => 'Makan siang',
            'transaction_date' => $today,
        ]);

        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'type' => 'expense',
            'amount' => 25000,
            'base_amount' => 25000,
            'currency' => 'IDR',
            'description' => 'Kopi',
            'transaction_date' => $today,
        ]);

        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'type' => 'income',
            'amount' => 200000,
            'base_amount' => 200000,
            'currency' => 'IDR',
            'description' => 'Freelance',
            'transaction_date' => $today,
        ]);

        // Yesterday — should NOT appear
        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'type' => 'expense',
            'amount' => 99999,
            'base_amount' => 99999,
            'currency' => 'IDR',
            'description' => 'Kemarin',
            'transaction_date' => now()->subDay()->toDateString(),
        ]);

        $response = $this->action->handle($this->makeUpdate('/today'));

        $this->assertStringContainsString('📅', $response['text']);
        $this->assertStringContainsString('Makan siang', $response['text']);
        $this->assertStringContainsString('Kopi', $response['text']);
        $this->assertStringContainsString('Freelance', $response['text']);
        $this->assertStringContainsString('200.000', $response['text']); // income total
        $this->assertStringContainsString('75.000', $response['text']); // expense total
        $this->assertStringNotContainsString('Kemarin', $response['text']);
    }

    public function test_today_command_shows_empty_state(): void
    {
        $response = $this->action->handle($this->makeUpdate('/today'));

        $this->assertStringContainsString('belum ada', strtolower($response['text']));
    }

    // ============================================================
    // /month
    // ============================================================

    public function test_month_command_shows_current_month_summary(): void
    {
        $thisMonth = now()->startOfMonth()->toDateString();

        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'type' => 'income',
            'amount' => 10_000_000,
            'base_amount' => 10_000_000,
            'currency' => 'IDR',
            'description' => 'Gaji',
            'transaction_date' => $thisMonth,
        ]);

        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'type' => 'expense',
            'amount' => 3_500_000,
            'base_amount' => 3_500_000,
            'currency' => 'IDR',
            'description' => 'Sewa',
            'transaction_date' => $thisMonth,
        ]);

        // Last month — should NOT appear
        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'type' => 'expense',
            'amount' => 500_000,
            'base_amount' => 500_000,
            'currency' => 'IDR',
            'description' => 'Bulan lalu',
            'transaction_date' => now()->subMonth()->toDateString(),
        ]);

        $response = $this->action->handle($this->makeUpdate('/month'));

        $this->assertStringContainsString('📊', $response['text']);
        $this->assertStringContainsString('10.000.000', $response['text']); // income
        $this->assertStringContainsString('3.500.000', $response['text']); // expense
        $this->assertStringContainsString('6.500.000', $response['text']); // net
        $this->assertStringNotContainsString('Bulan lalu', $response['text']);
    }

    public function test_month_command_shows_empty_state(): void
    {
        $response = $this->action->handle($this->makeUpdate('/month'));

        $this->assertStringContainsString('belum ada', strtolower($response['text']));
    }

    // ============================================================
    // /budget
    // ============================================================

    public function test_budget_command_shows_budget_utilization(): void
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
            'amount' => 1_000_000,
            'currency' => 'IDR',
            'period' => Frequency::Monthly,
            'notification_threshold' => 80,
        ]);

        // Spending within this budget's category this month
        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 350_000,
            'base_amount' => 350_000,
            'currency' => 'IDR',
            'description' => 'Makan di resto',
            'transaction_date' => now()->toDateString(),
        ]);

        $response = $this->action->handle($this->makeUpdate('/budget'));

        $this->assertStringContainsString('💳', $response['text']);
        $this->assertStringContainsString('Makanan', $response['text']);
        $this->assertStringContainsString('35', $response['text']); // percent
        $this->assertStringContainsString('350.000', $response['text']); // spent
        $this->assertStringContainsString('1.000.000', $response['text']); // budget amount
    }

    public function test_budget_command_shows_empty_state(): void
    {
        $response = $this->action->handle($this->makeUpdate('/budget'));

        $this->assertStringContainsString('belum ada', strtolower($response['text']));
    }

    // ============================================================
    // /categories
    // ============================================================

    public function test_categories_command_lists_all_active_categories(): void
    {
        $expenseCat = Category::factory()->create([
            'team_id' => $this->user->current_team_id,
            'name' => 'Transportasi',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $incomeCat = Category::factory()->create([
            'team_id' => $this->user->current_team_id,
            'name' => 'Gaji',
            'type' => 'income',
            'is_active' => true,
        ]);

        // Inactive — should NOT appear
        Category::factory()->create([
            'team_id' => $this->user->current_team_id,
            'name' => 'Kategori Lama',
            'type' => 'expense',
            'is_active' => false,
        ]);

        $response = $this->action->handle($this->makeUpdate('/categories'));

        $this->assertStringContainsString('📂', $response['text']);
        $this->assertStringContainsString('Transportasi', $response['text']);
        $this->assertStringContainsString('Gaji', $response['text']);
        $this->assertStringContainsString('Pengeluaran', $response['text']);
        $this->assertStringContainsString('Pemasukan', $response['text']);
        $this->assertStringNotContainsString('Kategori Lama', $response['text']);
    }

    public function test_categories_command_shows_empty_state(): void
    {
        // Delete all categories
        Category::where('team_id', $this->user->current_team_id)->delete();

        $response = $this->action->handle($this->makeUpdate('/categories'));

        $this->assertStringContainsString('belum ada', strtolower($response['text']));
    }

    // ============================================================
    // /help (existing, verify it includes new commands)
    // ============================================================

    public function test_help_command_lists_all_commands(): void
    {
        $response = $this->action->handle($this->makeUpdate('/help'));

        $this->assertStringContainsString('/balance', $response['text']);
        $this->assertStringContainsString('/today', $response['text']);
        $this->assertStringContainsString('/month', $response['text']);
        $this->assertStringContainsString('/budget', $response['text']);
        $this->assertStringContainsString('/categories', $response['text']);
        $this->assertStringContainsString('/help', $response['text']);
        $this->assertStringContainsString('/start', $response['text']);
    }

    // ============================================================
    // Unlinked user — all commands should return link prompt
    // ============================================================

    public function test_commands_return_link_prompt_for_unlinked_user(): void
    {
        // Create unlinked telegram user
        $unlinkedUser = TelegramUser::create([
            'user_id' => null,
            'chat_id' => 999999999,
            'username' => 'unlinked',
            'first_name' => 'Unlinked',
            'is_active' => true,
        ]);

        $unlinkedUpdate = function (string $text) {
            return [
                'update_id' => 88888,
                'message' => [
                    'message_id' => 99,
                    'chat' => ['id' => 999999999, 'type' => 'private'],
                    'from' => ['id' => 777777777, 'is_bot' => false, 'first_name' => 'Unlinked', 'username' => 'unlinked'],
                    'date' => time(),
                    'text' => $text,
                ],
            ];
        };

        foreach (['/balance', '/today', '/month', '/budget', '/categories'] as $cmd) {
            $response = $this->action->handle($unlinkedUpdate($cmd));
            $this->assertStringContainsString(
                'hubungkan',
                strtolower($response['text']),
                "Command {$cmd} should prompt unlinked user to connect"
            );
        }
    }
}
