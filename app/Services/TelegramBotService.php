<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramBotService
{
    private string $token;
    private string $baseUrl;

    public function __construct()
    {
        $this->token = config('services.telegram.bot_token');
        $this->baseUrl = "https://api.telegram.org/bot{$this->token}";
    }

    public function sendMessage(string $chatId, string $text, ?string $parseMode = null, ?array $replyMarkup = null): array
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode ?? 'HTML',
        ];

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        return Http::post("{$this->baseUrl}/sendMessage", $payload)->json();
    }

    public function sendPhoto(string $chatId, string $photoUrl, ?string $caption = null): array
    {
        $payload = [
            'chat_id' => $chatId,
            'photo' => $photoUrl,
        ];

        if ($caption !== null) {
            $payload['caption'] = $caption;
        }

        return Http::post("{$this->baseUrl}/sendPhoto", $payload)->json();
    }

    public function getFileUrl(string $fileId): ?string
    {
        $response = Http::get("{$this->baseUrl}/getFile", ['file_id' => $fileId])->json();

        if (! ($response['ok'] ?? false)) {
            return null;
        }

        $filePath = $response['result']['file_path'] ?? null;

        if (! $filePath) {
            return null;
        }

        return "https://api.telegram.org/file/bot{$this->token}/{$filePath}";
    }

    public function getFile(string $fileId): ?array
    {
        $response = Http::get("{$this->baseUrl}/getFile", ['file_id' => $fileId])->json();

        if (! ($response['ok'] ?? false)) {
            return null;
        }

        return $response['result'];
    }

    public function downloadFile(string $filePath, string $savePath): bool
    {
        $url = "https://api.telegram.org/file/bot{$this->token}/{$filePath}";
        $response = Http::get($url);

        if ($response->successful()) {
            file_put_contents($savePath, $response->body());
            return true;
        }

        return false;
    }

    public function setWebhook(): array
    {
        $url = config('services.telegram.webhook_url');
        $secretToken = config('services.telegram.webhook_secret');

        return Http::post("{$this->baseUrl}/setWebhook", [
            'url' => $url,
            'secret_token' => $secretToken,
            'allowed_updates' => ['message', 'callback_query'],
        ])->json();
    }

    public function deleteWebhook(): array
    {
        return Http::post("{$this->baseUrl}/deleteWebhook")->json();
    }

    public function getWebhookInfo(): array
    {
        return Http::get("{$this->baseUrl}/getWebhookInfo")->json();
    }
}
