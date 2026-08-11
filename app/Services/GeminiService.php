<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private ?string $apiKey;

    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
    }

    /**
     * Check if the Gemini API key is configured.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * Parse a natural-language Indonesian transaction message into structured data.
     *
     * @param  string  $text  Raw message like "Tanggal 10 Agt 2026 Bayar BPJS Sofie 452500"
     * @return array{amount: ?int, description: string, type: string, category_suggestion: ?string, date: ?string, merchant: ?string, error: ?string}
     *
     * @throws \RuntimeException On API or parsing failure.
     */
    public function parseTransactionText(string $text): array
    {
        $systemPrompt = "Kamu adalah parser transaksi keuangan Bahasa Indonesia. Parse pesan berikut ke JSON dengan field:\n- amount: integer (jumlah uang dalam rupiah)\n- description: string (deskripsi transaksi, tanpa tanggal dan jumlah)\n- type: \"income\" atau \"expense\"\n- category: string (kategori transaksi dalam bahasa indonesia)\n- date: Y-m-d atau null\n- merchant: string (nama toko/aplikasi pembayaran, misal: DANA, GoPay, Alfamart, Tokopedia) atau null\n\nHanya return JSON, tidak ada teks lain.";

        $userPrompt = "Parse this Indonesian transaction message into JSON: {amount, description, type (income/expense), category, date (Y-m-d if present), merchant}. Text: {$text}";

        try {
            $response = Http::timeout(30)
                ->post(self::API_URL . '?key=' . urlencode($this->apiKey), [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $systemPrompt . "\n\n" . $userPrompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'maxOutputTokens' => 256,
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('Gemini API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \RuntimeException('Gemini API returned ' . $response->status());
            }

            $data = $response->json();
            $textResponse = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            // Strip markdown JSON fences if present
            $textResponse = trim($textResponse);
            $textResponse = preg_replace('/^```(?:json)?\s*/', '', $textResponse);
            $textResponse = preg_replace('/\s*```$/', '', $textResponse);

            $parsed = json_decode($textResponse, true);

            if (! is_array($parsed)) {
                Log::warning('Gemini returned invalid JSON', ['response' => $textResponse]);
                throw new \RuntimeException('Gemini returned invalid JSON');
            }

            return $this->normalizeResponse($parsed, $text);
        } catch (\Throwable $e) {
            Log::warning('Gemini parse failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Normalize the Gemini JSON response into the standard parser output format.
     */
    private function normalizeResponse(array $parsed, string $originalText): array
    {
        return [
            'amount' => isset($parsed['amount']) ? (int) $parsed['amount'] : null,
            'description' => $parsed['description'] ?? $originalText,
            'type' => in_array($parsed['type'] ?? '', ['income', 'expense'], true) ? $parsed['type'] : 'expense',
            'category_suggestion' => $parsed['category'] ?? null,
            'date' => $parsed['date'] ?? null,
            'merchant' => $parsed['merchant'] ?? null,
            'error' => null,
        ];
    }
}
