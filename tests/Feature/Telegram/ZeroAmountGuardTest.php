<?php

namespace Tests\Feature\Telegram;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Actions\Telegram\ProcessMessageAction;
use App\Actions\Transactions\CreateTransactionAction;
use App\Models\Account;
use App\Models\Category;
use App\Models\TelegramUser;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AiParserService;
use App\Services\CurrencyConverterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ZeroAmountGuardTest extends TestCase
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

    protected function makeUpdate(string $text): array
    {
        return [
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
        ];
    }

    protected function makeCallbackUpdate(array $parsedData, int $categoryId): array
    {
        $teamId = $this->user->current_team_id;
        $payload = array_merge($parsedData, ['category_id' => $categoryId]);
        $compact = [
            'a' => $payload['amount'],
            'd' => $payload['description'] ?? '',
            't' => ($payload['type'] ?? 'expense') === 'income' ? 'i' : 'e',
            'c' => $categoryId,
        ];
        $callbackData = 'cat:' . $teamId . ':' . base64_encode(json_encode($compact));

        return [
            'update_id' => 12346,
            'callback_query' => [
                'id' => 'callback_zero',
                'from' => [
                    'id' => 987654321,
                    'is_bot' => false,
                    'first_name' => 'Test',
                ],
                'message' => [
                    'message_id' => 42,
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

    public function test_halo_with_ai_zero_amount_does_not_create_transaction_or_show_category_keyboard(): void
    {
        $mock = $this->createMock(AiParserService::class);
        $mock->method('isConfigured')->willReturn(true);
        $mock->method('parseTransactionText')->willReturn([
            'amount' => 0,
            'description' => 'Halo',
            'type' => 'expense',
            'category_suggestion' => null,
            'date' => null,
            'merchant' => null,
            'error' => null,
        ]);
        $this->app->instance(AiParserService::class, $mock);

        $action = new ProcessMessageAction;
        $response = $action->handle($this->makeUpdate('Halo'));

        $this->assertStringContainsString('jumlah uang', strtolower($response['text']));
        $this->assertArrayNotHasKey('reply_markup', $response);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_callback_with_zero_amount_does_not_create_transaction(): void
    {
        $action = new ProcessMessageAction;
        $response = $action->handle($this->makeCallbackUpdate([
            'amount' => 0,
            'description' => 'Halo',
            'type' => 'expense',
        ], $this->expenseCategory->id));

        $this->assertEquals('callback_edit', $response['type']);
        $this->assertStringContainsString('lebih besar dari 0', $response['text']);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_create_transaction_action_rejects_zero_amount(): void
    {
        $action = new CreateTransactionAction(new CurrencyConverterService);

        $this->expectException(ValidationException::class);

        try {
            $action->execute([
                'team_id' => $this->user->current_team_id,
                'user_id' => $this->user->id,
                'account_id' => $this->account->id,
                'type' => 'expense',
                'amount' => 0,
                'currency' => 'IDR',
                'description' => 'Test',
                'transaction_date' => now()->toDateString(),
                'source' => 'telegram',
            ]);
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
            $this->assertStringContainsString('lebih besar dari 0', $e->errors()['amount'][0]);
            throw $e;
        }
    }
}
