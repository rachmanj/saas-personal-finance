<?php

namespace Tests\Feature\Telegram;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Actions\Telegram\ProcessMessageAction;
use App\Models\Account;
use App\Models\Category;
use App\Models\TelegramUser;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessMessageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Account $account;
    protected Category $expenseCategory;
    protected TelegramUser $telegramUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->user = User::factory()->create();
        (new CreatePersonalTeamAction)->execute($this->user);

        $this->account = Account::factory()->create([
            'team_id' => $this->user->current_team_id,
            'currency' => 'IDR',
            'balance' => 1_000_000,
            'is_active' => true,
        ]);

        $this->expenseCategory = Category::factory()->create([
            'team_id' => $this->user->current_team_id,
            'name' => 'Makanan & Minuman',
            'type' => 'expense',
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

    public function test_process_text_message_creates_expense_transaction(): void
    {
        $update = $this->makeUpdate('makan siang 50rb');

        $action = new ProcessMessageAction;
        $response = $action->handle($update);

        // Assert response
        $this->assertIsString($response['text']);
        $this->assertStringContainsString('makan siang', $response['text']);
        $this->assertStringContainsString('50.000', $response['text']);

        // Assert transaction created
        $this->assertDatabaseHas('transactions', [
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'description' => 'makan siang',
            'type' => 'expense',
            'source' => 'telegram',
        ]);

        // Assert TelegramMessage saved
        $this->assertDatabaseHas('telegram_messages', [
            'telegram_user_id' => $this->telegramUser->id,
            'direction' => 'inbound',
            'message_type' => 'text',
            'content' => 'makan siang 50rb',
            'status' => 'processed',
        ]);
    }

    public function test_process_text_message_creates_income_transaction(): void
    {
        $update = $this->makeUpdate('gaji 5 juta');

        $action = new ProcessMessageAction;
        $response = $action->handle($update);

        $this->assertStringContainsString('5.000.000', $response['text']);

        $this->assertDatabaseHas('transactions', [
            'team_id' => $this->user->current_team_id,
            'amount' => 5_000_000,
            'type' => 'income',
            'source' => 'telegram',
        ]);
    }

    public function test_process_message_handles_no_amount(): void
    {
        $update = $this->makeUpdate('halo apa kabar');

        $action = new ProcessMessageAction;
        $response = $action->handle($update);

        $this->assertStringContainsString('Maaf', $response['text']);
    }

    public function test_process_message_saves_telegram_message_record(): void
    {
        $update = $this->makeUpdate('beli bensin 100k');

        $action = new ProcessMessageAction;
        $action->handle($update);

        $this->assertDatabaseHas('telegram_messages', [
            'telegram_user_id' => $this->telegramUser->id,
            'direction' => 'inbound',
            'message_type' => 'text',
            'content' => 'beli bensin 100k',
            'status' => 'processed',
        ]);
    }

    public function test_process_message_auto_categorizes(): void
    {
        // Create a matching categorization rule
        \App\Models\CategorizationRule::create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'pattern' => 'makan',
            'category_id' => $this->expenseCategory->id,
            'confidence' => 1.0,
            'source' => 'manual',
        ]);

        $update = $this->makeUpdate('makan siang 50rb');

        $action = new ProcessMessageAction;
        $action->handle($update);

        $transaction = Transaction::where('description', 'makan siang')->first();
        $this->assertNotNull($transaction);
        $this->assertEquals($this->expenseCategory->id, $transaction->category_id);
    }

    public function test_process_message_handles_unlinked_telegram_user(): void
    {
        // Create unlinked telegram user
        $unlinkedUser = TelegramUser::create([
            'user_id' => null,
            'chat_id' => 999999999,
            'username' => 'unlinked',
            'first_name' => 'Unlinked',
            'is_active' => true,
        ]);

        $update = $this->makeUpdate('makan 50rb', [
            'message' => [
                'message_id' => 99,
                'chat' => ['id' => 999999999, 'type' => 'private'],
                'from' => ['id' => 888888888, 'is_bot' => false, 'first_name' => 'Unlinked', 'username' => 'unlinked'],
                'date' => time(),
                'text' => 'makan 50rb',
            ],
        ]);

        $action = new ProcessMessageAction;
        $response = $action->handle($update);

        // Should ask to link account
        $this->assertStringContainsString('hubungkan', strtolower($response['text']));
    }

    public function test_process_message_handles_new_user_on_start_command(): void
    {
        $update = [
            'update_id' => 99999,
            'message' => [
                'message_id' => 100,
                'chat' => [
                    'id' => 111222333,
                    'type' => 'private',
                ],
                'from' => [
                    'id' => 444555666,
                    'is_bot' => false,
                    'first_name' => 'New',
                    'username' => 'newuser',
                ],
                'date' => time(),
                'text' => '/start',
            ],
        ];

        $action = new ProcessMessageAction;
        $response = $action->handle($update);

        // Should create TelegramUser
        $this->assertDatabaseHas('telegram_users', [
            'chat_id' => 111222333,
            'username' => 'newuser',
            'user_id' => null,
        ]);

        $this->assertStringContainsString('Halo', $response['text']);
    }

    public function test_process_message_handles_help_command(): void
    {
        $update = $this->makeUpdate('/help');

        $action = new ProcessMessageAction;
        $response = $action->handle($update);

        $this->assertStringContainsString('Ngopi Dulu Donk', $response['text']);
    }

    public function test_process_message_handles_photo_message(): void
    {
        $update = [
            'update_id' => 88888,
            'message' => [
                'message_id' => 5,
                'chat' => ['id' => 123456789, 'type' => 'private'],
                'from' => ['id' => 987654321, 'is_bot' => false, 'first_name' => 'Test', 'username' => 'testuser'],
                'date' => time(),
                'photo' => [['file_id' => 'ABC123', 'file_unique_id' => 'unique123']],
            ],
        ];

        $action = new ProcessMessageAction;
        $response = $action->handle($update);

        $this->assertStringContainsString('segera hadir', strip_tags($response['text']));
    }

    public function test_process_message_handles_voice_message(): void
    {
        $update = [
            'update_id' => 77777,
            'message' => [
                'message_id' => 6,
                'chat' => ['id' => 123456789, 'type' => 'private'],
                'from' => ['id' => 987654321, 'is_bot' => false, 'first_name' => 'Test', 'username' => 'testuser'],
                'date' => time(),
                'voice' => ['file_id' => 'VOICE123', 'duration' => 5],
            ],
        ];

        $action = new ProcessMessageAction;
        $response = $action->handle($update);

        $this->assertStringContainsString('segera hadir', strip_tags($response['text']));
    }

    public function test_delete_command_deletes_transaction_by_id(): void
    {
        // Create a transaction to delete
        $transaction = Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'type' => 'expense',
            'amount' => 50000,
            'description' => 'makan siang',
            'transaction_date' => now()->toDateString(),
            'source' => 'telegram',
        ]);

        $update = $this->makeUpdate('/delete ' . $transaction->id);

        $action = new ProcessMessageAction;
        $response = $action->handle($update);

        // Should confirm deletion
        $this->assertStringContainsString('🗑️', $response['text']);
        $this->assertStringContainsString('Transaksi', $response['text']);
        $this->assertStringContainsString('dihapus', $response['text']);
        $this->assertStringContainsString((string) $transaction->id, $response['text']);

        // Transaction should be soft-deleted
        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
    }

    public function test_delete_command_with_nonexistent_id_returns_not_found(): void
    {
        $update = $this->makeUpdate('/delete 99999');

        $action = new ProcessMessageAction;
        $response = $action->handle($update);

        $this->assertStringContainsString('Transaksi tidak ditemukan', $response['text']);
    }

    public function test_delete_command_requires_linked_account(): void
    {
        // Create unlinked telegram user
        $unlinkedUser = TelegramUser::create([
            'user_id' => null,
            'chat_id' => 999999999,
            'username' => 'unlinked',
            'first_name' => 'Unlinked',
            'is_active' => true,
        ]);

        $update = $this->makeUpdate('/delete 1', [
            'message' => [
                'message_id' => 99,
                'chat' => ['id' => 999999999, 'type' => 'private'],
                'from' => ['id' => 888888888, 'is_bot' => false, 'first_name' => 'Unlinked', 'username' => 'unlinked'],
                'date' => time(),
                'text' => '/delete 1',
            ],
        ]);

        $action = new ProcessMessageAction;
        $response = $action->handle($update);

        // Should ask to link account
        $this->assertStringContainsString('hubungkan', strtolower($response['text']));
    }

    public function test_delete_command_with_text_only_returns_not_found(): void
    {
        $update = $this->makeUpdate('/delete');

        $action = new ProcessMessageAction;
        $response = $action->handle($update);

        $this->assertStringContainsString('Transaksi tidak ditemukan', $response['text']);
    }
}
