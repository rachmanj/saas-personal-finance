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

    /**
     * Parse raw OCR receipt text to extract structured receipt data.
     * Returns: merchant, items (comma-separated), amount, date.
     */
    public function parseReceipt(string $text): array
    {
        if ($this->provider !== 'deepseek') {
            return ['merchant' => null, 'items' => null, 'amount' => null, 'date' => null];
        }

        $systemPrompt = "Kamu adalah parser struk belanja Bahasa Indonesia. Ekstrak dari teks struk berikut ke JSON.\n"
            . "Field:\n"
            . "- merchant: string (nama toko, contoh: Fore Coffee)\n"
            . "- items: string (daftar item yang dibeli, pisahkan dengan koma, tanpa harga dan jumlah. Contoh: \"Regular Hot Americano, Butter Croissant, Tas Belanja\")\n"
            . "- amount: integer (total pembayaran dalam rupiah)\n"
            . "- date: string (Y-m-d atau null)\n"
            . "Abaikan alamat toko, NPWP, nama customer, nomor order, dan informasi pajak.\n"
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
                    'max_tokens' => 400,
                ]);

            if (!$response->successful()) {
                throw new \RuntimeException('DeepSeek API error');
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';
            $content = trim(preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $content));
            $parsed = json_decode($content, true);

            if (!is_array($parsed)) {
                throw new \RuntimeException('Invalid JSON');
            }

            return [
                'merchant' => $parsed['merchant'] ?? null,
                'items' => $parsed['items'] ?? null,
                'amount' => isset($parsed['amount']) ? (int)$parsed['amount'] : null,
                'date' => $parsed['date'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::warning('DeepSeek receipt parse failed: ' . $e->getMessage());
            return ['merchant' => null, 'items' => null, 'amount' => null, 'date' => null];
        }
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
