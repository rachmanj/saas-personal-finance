<?php

namespace Tests\Feature\Telegram;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Actions\Telegram\ProcessMessageAction;
use App\Models\Account;
use App\Models\TelegramUser;
use App\Models\User;
use App\Services\TelegramBotService;
use App\Services\VoiceTranscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VoiceMessageTest extends TestCase
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
            'api.telegram.org/file/*' => Http::response('fake-audio-data', 200),
        ]);
    }

    protected function makeVoiceUpdate(array $overrides = []): array
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
                'voice' => [
                    'file_id' => 'VOICE123',
                    'file_unique_id' => 'unique_voice',
                    'duration' => 5,
                    'mime_type' => 'audio/ogg',
                ],
            ],
        ], $overrides);
    }

    public function test_process_voice_message_downloads_file_transcribes_and_creates_transaction(): void
    {
        // Mock TelegramBotService::getFileUrl
        $this->mock(TelegramBotService::class, function ($mock) {
            $mock->shouldReceive('getFileUrl')
                ->once()
                ->with('VOICE123')
                ->andReturn('https://api.telegram.org/file/botTOKEN/voice.ogg');
        });

        // Mock VoiceTranscriptionService::transcribe
        $this->mock(VoiceTranscriptionService::class, function ($mock) {
            $mock->shouldReceive('transcribe')
                ->once()
                ->andReturn('Makan siang 50 ribu hari ini');
        });

        $update = $this->makeVoiceUpdate();

        $action = new ProcessMessageAction;
        $response = $action->handle($update);

        // Assert reply format
        $this->assertIsString($response['text']);
        $this->assertStringContainsString('🎤 Terproses', $response['text']);
        $this->assertStringContainsString('50.000', $response['text']);

        // Assert transaction created
        $this->assertDatabaseHas('transactions', [
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'source' => 'telegram',
        ]);

        // Assert TelegramMessage saved
        $this->assertDatabaseHas('telegram_messages', [
            'telegram_user_id' => $this->telegramUser->id,
            'direction' => 'inbound',
            'message_type' => 'voice',
            'status' => 'processed',
        ]);
    }

    public function test_process_voice_message_handles_income_transcript(): void
    {
        $this->mock(TelegramBotService::class, function ($mock) {
            $mock->shouldReceive('getFileUrl')
                ->once()
                ->andReturn('https://api.telegram.org/file/botTOKEN/voice.ogg');
        });

        $this->mock(VoiceTranscriptionService::class, function ($mock) {
            $mock->shouldReceive('transcribe')
                ->once()
                ->andReturn('Gaji masuk 10 juta');
        });

        $update = $this->makeVoiceUpdate();

        $action = new ProcessMessageAction;
        $response = $action->handle($update);

        $this->assertStringContainsString('🎤 Terproses', $response['text']);
        $this->assertStringContainsString('10.000.000', $response['text']);

        $this->assertDatabaseHas('transactions', [
            'amount' => 10_000_000,
            'type' => 'income',
            'source' => 'telegram',
        ]);
    }

    public function test_process_voice_message_handles_unlinked_user(): void
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
                ->andReturn('https://api.telegram.org/file/botTOKEN/voice.ogg');
        });

        $this->mock(VoiceTranscriptionService::class, function ($mock) {
            $mock->shouldReceive('transcribe')
                ->once()
                ->andReturn('Makan siang 50 ribu');
        });

        $update = $this->makeVoiceUpdate([
            'message' => [
                'message_id' => 99,
                'chat' => ['id' => 999999999, 'type' => 'private'],
                'from' => ['id' => 888888888, 'is_bot' => false, 'first_name' => 'Unlinked', 'username' => 'unlinked'],
                'date' => time(),
                'voice' => [
                    'file_id' => 'VOICE999',
                    'file_unique_id' => 'vu999',
                    'duration' => 3,
                ],
            ],
        ]);

        $action = new ProcessMessageAction;
        $response = $action->handle($update);

        // Should ask to link account
        $this->assertStringContainsString('hubungkan', strtolower($response['text']));
    }

    public function test_process_voice_message_saves_telegram_message_record(): void
    {
        $this->mock(TelegramBotService::class, function ($mock) {
            $mock->shouldReceive('getFileUrl')
                ->once()
                ->andReturn('https://api.telegram.org/file/botTOKEN/voice.ogg');
        });

        $this->mock(VoiceTranscriptionService::class, function ($mock) {
            $mock->shouldReceive('transcribe')
                ->once()
                ->andReturn('Beli bensin 200 ribu di Shell');
        });

        $update = $this->makeVoiceUpdate();

        $action = new ProcessMessageAction;
        $action->handle($update);

        $this->assertDatabaseHas('telegram_messages', [
            'telegram_user_id' => $this->telegramUser->id,
            'direction' => 'inbound',
            'message_type' => 'voice',
            'file_id' => 'VOICE123',
            'status' => 'processed',
        ]);
    }
}
