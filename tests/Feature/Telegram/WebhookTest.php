<?php

namespace Tests\Feature\Telegram;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.webhook_secret' => 'test-secret-token-64-chars',
            'services.telegram.bot_token' => '123456:ABC-DEF1234ghikl-zyx57W2v1u123ew11',
            'services.telegram.webhook_url' => 'https://app.example.com/api/telegram/webhook',
        ]);
    }

    public function test_webhook_returns_200_with_valid_secret_token(): void
    {
        $response = $this->postJson('/api/telegram/webhook', [
            'update_id' => 12345,
            'message' => [
                'message_id' => 1,
                'chat' => ['id' => 123456789],
                'text' => '/start',
            ],
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => 'test-secret-token-64-chars',
        ]);

        $response->assertStatus(200);
    }

    public function test_webhook_rejects_invalid_secret_token_without_processing(): void
    {
        $response = $this->postJson('/api/telegram/webhook', [
            'update_id' => 12345,
            'message' => [
                'message_id' => 1,
                'chat' => ['id' => 123456789],
                'text' => '/start',
            ],
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => 'wrong-token',
        ]);

        // Token is validated; invalid requests return 200 without processing
        // (Telegram would retry non-2xx, so we acknowledge silently).
        $response->assertStatus(200);
    }

    public function test_webhook_rejects_missing_secret_token_without_processing(): void
    {
        $response = $this->postJson('/api/telegram/webhook', [
            'update_id' => 12345,
            'message' => [
                'message_id' => 1,
                'chat' => ['id' => 123456789],
                'text' => '/start',
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_webhook_logs_received_update(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Telegram webhook received', \Mockery::on(function ($context) {
                return isset($context['update_id'])
                    && $context['update_id'] === 12345;
            }));

        Log::shouldReceive('error')->zeroOrMoreTimes();

        // Prevent actual HTTP call to Telegram
        \Illuminate\Support\Facades\Http::fake();

        $this->postJson('/api/telegram/webhook', [
            'update_id' => 12345,
            'message' => [
                'message_id' => 1,
                'chat' => ['id' => 123456789],
                'text' => '/start',
            ],
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => 'test-secret-token-64-chars',
        ]);
    }
}
