<?php

namespace Tests\Feature\Telegram;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Actions\Telegram\ProcessMessageAction;
use App\Models\Account;
use App\Models\TelegramUser;
use App\Models\User;
use App\Services\OcrService;
use App\Services\TelegramBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoMessageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Account $account;
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

        $this->telegramUser = TelegramUser::create([
            'user_id' => $this->user->id,
            'chat_id' => 123456789,
            'username' => 'testuser',
            'first_name' => 'Test',
            'is_active' => true,
            'linked_at' => now(),
        ]);

        $this->actingAs($this->user);

        // Fake storage
        Storage::fake('local');

        // Fake HTTP responses for Telegram file download
        Http::fake([
            'api.telegram.org/file/*' => Http::response('fake-image-data', 200),
        ]);
    }

    protected function makePhotoUpdate(array $overrides = []): array
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
                'photo' => [
                    ['file_id' => 'small_photo', 'file_unique_id' => 'unique_small', 'width' => 100, 'height' => 100],
                    ['file_id' => 'large_photo', 'file_unique_id' => 'unique_large', 'width' => 800, 'height' => 600],
                ],
            ],
        ], $overrides);
    }

    public function test_process_photo_message_downloads_file_and_runs_ocr(): void
    {
        // Mock TelegramBotService::getFileUrl to return a fake URL
        $this->mock(TelegramBotService::class, function ($mock) {
            $mock->shouldReceive('getFileUrl')
                ->once()
                ->with('large_photo')
                ->andReturn('https://api.telegram.org/file/botTOKEN/photo.jpg');
        });

        // Mock OcrService::parse to return predictable data
        $this->mock(OcrService::class, function ($mock) {
            $mock->shouldReceive('parse')
                ->once()
                ->andReturn([
                    'merchant' => 'Starbucks',
                    'amount' => 75000,
                    'date' => now()->toDateString(),
                    'raw_text' => 'Starbucks 75000',
                ]);
        });

        $update = $this->makePhotoUpdate();

        $action = new ProcessMessageAction;
        $response = $action->handle($update);

        // Assert reply format
        $this->assertIsString($response['text']);
        $this->assertStringContainsString('📸 Struk diproses', $response['text']);
        $this->assertStringContainsString('Starbucks', $response['text']);
        $this->assertStringContainsString('75.000', $response['text']);

        // Assert transaction created
        $this->assertDatabaseHas('transactions', [
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'amount' => 75000,
            'currency' => 'IDR',
            'source' => 'telegram',
        ]);

        // Assert TelegramMessage saved
        $this->assertDatabaseHas('telegram_messages', [
            'telegram_user_id' => $this->telegramUser->id,
            'direction' => 'inbound',
            'message_type' => 'photo',
            'status' => 'processed',
        ]);
    }

    public function test_process_photo_message_handles_ocr_with_text_parsing(): void
    {
        // Mock TelegramBotService
        $this->mock(TelegramBotService::class, function ($mock) {
            $mock->shouldReceive('getFileUrl')
                ->once()
                ->andReturn('https://api.telegram.org/file/botTOKEN/photo.jpg');
        });

        // OCR returns raw text that ParseTransactionTextAction can parse
        $this->mock(OcrService::class, function ($mock) {
            $mock->shouldReceive('parse')
                ->once()
                ->andReturn([
                    'merchant' => 'Beli Bensin Shell',
                    'amount' => 200000,
                    'date' => now()->toDateString(),
                    'raw_text' => 'beli bensin 200rb di Shell',
                ]);
        });

        $update = $this->makePhotoUpdate();

        $action = new ProcessMessageAction;
        $response = $action->handle($update);

        $this->assertStringContainsString('📸 Struk diproses', $response['text']);
        $this->assertStringContainsString('200.000', $response['text']);

        $this->assertDatabaseHas('transactions', [
            'amount' => 200000,
            'type' => 'expense',
            'source' => 'telegram',
        ]);
    }

    public function test_process_photo_message_handles_unlinked_user(): void
    {
        // Create unlinked telegram user
        $unlinkedUser = TelegramUser::create([
            'user_id' => null,
            'chat_id' => 999999999,
            'username' => 'unlinked',
            'first_name' => 'Unlinked',
            'is_active' => true,
        ]);

        $this->mock(TelegramBotService::class, function ($mock) {
            $mock->shouldReceive('getFileUrl')
                ->once()
                ->andReturn('https://api.telegram.org/file/botTOKEN/photo.jpg');
        });

        $this->mock(OcrService::class, function ($mock) {
            $mock->shouldReceive('parse')
                ->once()
                ->andReturn([
                    'merchant' => 'Starbucks',
                    'amount' => 75000,
                    'date' => now()->toDateString(),
                    'raw_text' => 'Starbucks 75000',
                ]);
        });

        $update = $this->makePhotoUpdate([
            'message' => [
                'message_id' => 99,
                'chat' => ['id' => 999999999, 'type' => 'private'],
                'from' => ['id' => 888888888, 'is_bot' => false, 'first_name' => 'Unlinked', 'username' => 'unlinked'],
                'date' => time(),
                'photo' => [
                    ['file_id' => 'large_photo', 'file_unique_id' => 'u_large', 'width' => 800, 'height' => 600],
                ],
            ],
        ]);

        $action = new ProcessMessageAction;
        $response = $action->handle($update);

        // Should ask to link account
        $this->assertStringContainsString('hubungkan', strtolower($response['text']));
    }

    public function test_process_photo_message_saves_telegram_message_record(): void
    {
        $this->mock(TelegramBotService::class, function ($mock) {
            $mock->shouldReceive('getFileUrl')
                ->once()
                ->andReturn('https://api.telegram.org/file/botTOKEN/photo.jpg');
        });

        $this->mock(OcrService::class, function ($mock) {
            $mock->shouldReceive('parse')
                ->once()
                ->andReturn([
                    'merchant' => 'Walmart',
                    'amount' => 50000,
                    'date' => now()->toDateString(),
                    'raw_text' => 'Walmart 50000',
                ]);
        });

        $update = $this->makePhotoUpdate();

        $action = new ProcessMessageAction;
        $action->handle($update);

        $this->assertDatabaseHas('telegram_messages', [
            'telegram_user_id' => $this->telegramUser->id,
            'direction' => 'inbound',
            'message_type' => 'photo',
            'file_id' => 'large_photo',
            'status' => 'processed',
        ]);
    }
}
