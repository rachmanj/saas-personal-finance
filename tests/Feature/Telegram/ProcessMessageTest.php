<?php

namespace Tests\Feature\Telegram;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Actions\Telegram\ProcessMessageAction;
use App\Models\Account;
use App\Models\Category;
use App\Models\TelegramUser;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TelegramBotService;
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
            'icon' => '🍔',
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

    protected function makeCallbackUpdate(array $parsedData, int $categoryId, int $messageId = 42): array
    {
        $teamId = $this->user->current_team_id;
        $payload = array_merge($parsedData, ['category_id' => $categoryId]);
        $callbackData = 'cat:' . $teamId . ':' . base64_encode(json_encode($this->compactCallbackPayload($payload)));

        return [
            'update_id' => 12346,
            'callback_query' => [
                'id' => 'callback123',
                'from' => [
                    'id' => 987654321,
                    'is_bot' => false,
                    'first_name' => 'Test',
                    'username' => 'testuser',
                ],
                'message' => [
                    'message_id' => $messageId,
                    'chat' => [
                        'id' => 123456789,
                        'type' => 'private',
                    ],
                    'text' => 'Konfirmasi Transaksi',
                ],
                'data' => $callbackData,
            ],
        ];
    }

    /**
     * Mirror compact encoding used by ProcessMessageAction to stay within Telegram's 64-byte callback_data limit.
     */
    private function compactCallbackPayload(array $data): array
    {
        $compact = [
            'a' => $data['amount'],
            'd' => $data['description'] ?? '',
            't' => ($data['type'] ?? 'expense') === 'income' ? 'i' : 'e',
        ];

        if (! empty($data['date'])) {
            $compact['dt'] = $data['date'];
        }
        if (! empty($data['merchant'])) {
            $compact['m'] = $data['merchant'];
        }
        if (! empty($data['items'])) {
            $compact['i'] = $data['items'];
        }
        if (! empty($data['category_id'])) {
            $compact['c'] = $data['category_id'];
        }

        return $compact;
    }

    public function test_process_text_message_creates_expense_transaction(): void
    {
        $update = $this->makeUpdate('makan siang 50rb');

        $action = new ProcessMessageAction;
        $response = $action->handle($update);

        $this->assertStringContainsString('Konfirmasi Transaksi', $response['text']);
        $this->assertStringContainsString('makan siang', $response['text']);
        $this->assertStringContainsString('50.000', $response['text']);
        $this->assertArrayHasKey('reply_markup', $response);
        $this->assertArrayHasKey('inline_keyboard', $response['reply_markup']);

        $this->assertDatabaseMissing('transactions', [
            'description' => 'makan siang',
            'source' => 'telegram',
        ]);

        $callbackUpdate = $this->makeCallbackUpdate([
            'amount' => 50000,
            'description' => 'makan siang',
            'type' => 'expense',
        ], $this->expenseCategory->id);

        $callbackResponse = $action->handle($callbackUpdate);

        $this->assertEquals('callback_edit', $callbackResponse['type']);
        $this->assertStringContainsString('Tersimpan', $callbackResponse['text']);

        $this->assertDatabaseHas('transactions', [
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'description' => 'makan siang',
            'type' => 'expense',
            'source' => 'telegram',
            'category_id' => $this->expenseCategory->id,
        ]);
    }

    public function test_process_text_message_creates_income_transaction(): void
    {
        $incomeCategory = Category::factory()->create([
            'team_id' => $this->user->current_team_id,
            'name' => 'Gaji',
            'type' => 'income',
            'icon' => '💰',
            'is_active' => true,
        ]);

        $update = $this->makeUpdate('gaji 5 juta');

        $action = new ProcessMessageAction;
        $response = $action->handle($update);

        $this->assertStringContainsString('Konfirmasi Transaksi', $response['text']);
        $this->assertStringContainsString('5.000.000', $response['text']);
        $this->assertArrayHasKey('reply_markup', $response);

        $this->assertDatabaseMissing('transactions', [
            'type' => 'income',
            'source' => 'telegram',
        ]);

        $callbackUpdate = $this->makeCallbackUpdate([
            'amount' => 5_000_000,
            'description' => 'gaji',
            'type' => 'income',
        ], $incomeCategory->id);

        $callbackResponse = $action->handle($callbackUpdate);

        $this->assertStringContainsString('Tersimpan', $callbackResponse['text']);

        $this->assertDatabaseHas('transactions', [
            'team_id' => $this->user->current_team_id,
            'amount' => 5_000_000,
            'type' => 'income',
            'source' => 'telegram',
            'category_id' => $incomeCategory->id,
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
            'status' => 'pending',
        ]);
    }

    public function test_process_callback_query_creates_transaction(): void
    {
        $update = $this->makeCallbackUpdate([
            'amount' => 50000,
            'description' => 'makan siang',
            'type' => 'expense',
        ], $this->expenseCategory->id);

        $action = new ProcessMessageAction;
        $response = $action->handle($update);

        $this->assertEquals('callback_edit', $response['type']);
        $this->assertStringContainsString('Tersimpan', $response['text']);
        $this->assertStringContainsString((string) Transaction::first()->id, $response['text']);
        $this->assertStringContainsString('Makanan', $response['text']);

        $this->assertDatabaseHas('transactions', [
            'team_id' => $this->user->current_team_id,
            'amount' => 50000,
            'description' => 'makan siang',
            'category_id' => $this->expenseCategory->id,
        ]);
    }

    public function test_process_callback_query_updates_message(): void
    {
        config([
            'services.telegram.webhook_secret' => 'test-secret-token-64-chars',
            'services.telegram.bot_token' => '123456:ABC-DEF1234ghikl-zyx57W2v1u123ew11',
        ]);

        $parsed = [
            'amount' => 50000,
            'description' => 'makan siang',
            'type' => 'expense',
        ];

        $mock = \Mockery::mock(TelegramBotService::class);
        $mock->shouldReceive('answerCallbackQuery')
            ->once()
            ->with('callback123', null);
        $mock->shouldReceive('editMessageText')
            ->once()
            ->withArgs(function (string $chatId, int $messageId, string $text) {
                return $chatId === '123456789'
                    && $messageId === 42
                    && str_contains($text, 'Tersimpan');
            });
        $this->app->instance(TelegramBotService::class, $mock);

        $update = $this->makeCallbackUpdate($parsed, $this->expenseCategory->id);

        $this->postJson('/api/telegram/webhook', $update, [
            'X-Telegram-Bot-Api-Secret-Token' => 'test-secret-token-64-chars',
        ])->assertStatus(200);
    }

    public function test_process_message_handles_unlinked_telegram_user(): void
    {
        TelegramUser::create([
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

        $this->assertStringContainsString('Struk', strip_tags($response['text']));
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

        $this->assertStringContainsString('Struk', strip_tags($response['text']));
    }

    public function test_delete_command_deletes_transaction_by_id(): void
    {
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

        $this->assertStringContainsString('🗑️', $response['text']);
        $this->assertStringContainsString('Transaksi', $response['text']);
        $this->assertStringContainsString('dihapus', $response['text']);
        $this->assertStringContainsString((string) $transaction->id, $response['text']);

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
        TelegramUser::create([
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

        $this->assertStringContainsString('hubungkan', strtolower($response['text']));
    }

    public function test_delete_command_with_text_only_returns_not_found(): void
    {
        $update = $this->makeUpdate('/delete');

        $action = new ProcessMessageAction;
        $response = $action->handle($update);

        $this->assertStringContainsString('Transaksi tidak ditemukan', $response['text']);
    }

    public function test_kategori_tambah_adds_new_expense_category(): void
    {
        $update = $this->makeUpdate('/kategori tambah Kopi');

        $action = new ProcessMessageAction;
        $response = $action->handle($update);

        $this->assertStringContainsString('berhasil ditambahkan', $response['text']);
        $this->assertStringContainsString('Kopi', $response['text']);

        $this->assertDatabaseHas('categories', [
            'team_id' => $this->user->current_team_id,
            'name' => 'Kopi',
            'type' => 'expense',
        ]);
    }

    public function test_kategori_tambah_adds_income_category_with_type_suffix(): void
    {
        $update = $this->makeUpdate('/kategori tambah Gaji pemasukan');

        $action = new ProcessMessageAction;
        $response = $action->handle($update);

        $this->assertStringContainsString('berhasil ditambahkan', $response['text']);

        $this->assertDatabaseHas('categories', [
            'team_id' => $this->user->current_team_id,
            'name' => 'Gaji',
            'type' => 'income',
        ]);
    }

    public function test_kategori_tambah_rejects_duplicate(): void
    {
        $update = $this->makeUpdate('/kategori tambah Makanan & Minuman');

        $action = new ProcessMessageAction;
        $response = $action->handle($update);

        $this->assertStringContainsString('sudah ada', $response['text']);

        $this->assertEquals(1, Category::where('team_id', $this->user->current_team_id)
            ->where('name', 'Makanan & Minuman')->count());
    }
}
