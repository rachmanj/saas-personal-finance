<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiParserService
{
    private ?string $apiKey;
    private string $provider; // deepseek or gemini

    private const DEEPSEEK_URL = 'https://api.deepseek.com/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.deepseek.api_key');
        $this->provider = $this->apiKey ? 'deepseek' : (config('services.gemini.api_key') ? 'gemini' : '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey) || !empty(config('services.gemini.api_key'));
    }

    public function parseTransactionText(string $text): array
    {
        if ($this->provider === 'deepseek') {
            return $this->parseWithDeepSeek($text);
        }
        return $this->parseWithGemini($text);
    }

    private function parseWithDeepSeek(string $text): array
    {
        $systemPrompt = "Kamu adalah parser transaksi keuangan Bahasa Indonesia. Parse pesan berikut ke JSON.\n"
            . "Field: amount (integer rupiah), description (string), type (income/expense), category (string), date (Y-m-d atau null), merchant (string atau null).\n"
            . "Hanya return JSON, tidak ada teks lain.";

        try {
            $response = Http::timeout(30)
                ->withToken($this->apiKey)
                ->post(self::DEEPSEEK_URL, [
                    'model' => 'deepseek-chat',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $text],
                    ],
                    'temperature' => 0.1,
                    'max_tokens' => 256,
                ]);

            if (!$response->successful()) {
                Log::warning('DeepSeek API error', ['status' => $response->status()]);
                throw new \RuntimeException('DeepSeek API error');
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';
            $content = trim(preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $content));
            $parsed = json_decode($content, true);

            if (!is_array($parsed)) {
                throw new \RuntimeException('Invalid JSON from DeepSeek');
            }

            return [
                'amount' => isset($parsed['amount']) ? (int)$parsed['amount'] : null,
                'description' => $parsed['description'] ?? $text,
                'type' => in_array($parsed['type'] ?? '', ['income', 'expense']) ? $parsed['type'] : 'expense',
                'category_suggestion' => $parsed['category'] ?? null,
                'date' => $parsed['date'] ?? null,
                'merchant' => $parsed['merchant'] ?? null,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            Log::warning('DeepSeek parse failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function parseWithGemini(string $text): array
    {
        $gemini = new GeminiService;
        return $gemini->parseTransactionText($text);
    }
}
