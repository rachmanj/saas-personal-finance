<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OcrService
{
    private const OPENROUTER_URL = 'https://openrouter.ai/api/v1/chat/completions';

    private const VISION_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public function parse(string $filePath): array
    {
        if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'pdf') {
            return $this->parsePdf($filePath);
        }

        if ($this->shouldUseVision($filePath)) {
            try {
                return $this->parseWithVision($filePath);
            } catch (\Throwable $e) {
                Log::warning('OpenRouter vision OCR failed, falling back to tesseract', [
                    'file' => $filePath,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->parseImage($filePath);
    }

    public function parseWithVision(string $filePath): array
    {
        $apiKey = config('services.openrouter.api_key');
        if (empty($apiKey)) {
            throw new \RuntimeException('OpenRouter API key not configured');
        }

        if (!file_exists($filePath)) {
            throw new \RuntimeException('File not found: ' . $filePath);
        }

        $mimeType = $this->detectMimeType($filePath);
        $base64 = base64_encode(file_get_contents($filePath));
        $dataUrl = "data:{$mimeType};base64,{$base64}";

        $systemPrompt = "Kamu adalah parser struk belanja Bahasa Indonesia. Ekstrak data dari gambar struk belanja ke JSON.\n"
            . "Field:\n"
            . "- merchant: string (nama toko, contoh: Fore Coffee)\n"
            . "- items: string (daftar item yang dibeli, pisahkan dengan koma, tanpa harga dan jumlah. Contoh: \"Regular Hot Americano, Butter Croissant, Tas Belanja\")\n"
            . "- amount: integer (TOTAL pembayaran dalam rupiah — ambil angka di baris 'Total' / 'Total Pembayaran' / 'TOTAL'. JANGAN ambil 'Tunai', 'Bayar', 'Dibayar', atau 'Kembali'. Contoh: struk dengan Total=9.000, Tunai=10.000, Kembali=1.000 → amount=9000)\n"
            . "- date: string (Y-m-d atau null)\n"
            . "Abaikan alamat toko, NPWP, nama customer, nomor order, dan informasi pajak.\n"
            . "Hanya return JSON, tidak ada teks lain.";

        $response = Http::timeout(45)
            ->withToken($apiKey)
            ->withHeaders([
                'HTTP-Referer' => config('app.url'),
                'X-Title' => 'Ngopi Dulu Donk',
            ])
            ->post(self::OPENROUTER_URL, [
                'model' => config('services.openrouter.vision_model', 'openai/gpt-4o-mini'),
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => 'Ekstrak data dari struk belanja pada gambar ini.'],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => $dataUrl,
                                    'detail' => 'high',
                                ],
                            ],
                        ],
                    ],
                ],
                'temperature' => 0.1,
                'max_tokens' => 400,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('OpenRouter Vision API error: ' . $response->status() . ' ' . $response->body());
        }

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? '';
        $content = trim(preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $content));
        $parsed = json_decode($content, true);

        if (!is_array($parsed)) {
            throw new \RuntimeException('Invalid JSON from OpenRouter Vision');
        }

        $merchant = $parsed['merchant'] ?? null;
        $items = $parsed['items'] ?? null;
        $amount = isset($parsed['amount']) ? (int) $parsed['amount'] : null;
        $date = $parsed['date'] ?? null;

        return [
            'merchant' => $merchant,
            'amount' => $amount,
            'date' => $date,
            'raw_text' => $this->buildRawTextFromVision($merchant, $items, $parsed),
        ];
    }

    /**
     * Parse a PDF receipt: try text extraction first, then OCR each page.
     */
    public function parsePdf(string $filePath): array
    {
        $textFile = $filePath . '.txt';
        $cmd = sprintf('pdftotext -layout %s %s 2>/dev/null', escapeshellarg($filePath), escapeshellarg($textFile));
        exec($cmd, $o, $exitCode);

        $rawText = '';
        if ($exitCode === 0 && file_exists($textFile)) {
            $rawText = trim(file_get_contents($textFile));
            @unlink($textFile);
        }

        if (mb_strlen($rawText) < 20) {
            $rawText = $this->ocrPdfPages($filePath);
        }

        return $this->buildResult($rawText);
    }

    private function shouldUseVision(string $filePath): bool
    {
        if (!config('services.openrouter.vision_enabled', true)) {
            return false;
        }

        if (empty(config('services.openrouter.api_key'))) {
            return false;
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return in_array($ext, self::VISION_IMAGE_EXTENSIONS, true);
    }

    private function detectMimeType(string $filePath): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($ext) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/jpeg',
        };
    }

    private function buildRawTextFromVision(?string $merchant, ?string $items, array $parsed): string
    {
        $parts = array_filter([$merchant, $items]);
        if ($parts !== []) {
            return implode(', ', $parts);
        }

        return json_encode($parsed, JSON_UNESCAPED_UNICODE) ?: '';
    }

    /**
     * Convert PDF pages to PNG then OCR with tesseract.
     */
    private function ocrPdfPages(string $filePath): string
    {
        $imgPrefix = $filePath . '_page';
        $cmd = sprintf('pdftoppm -png -r 200 %s %s 2>/dev/null', escapeshellarg($filePath), escapeshellarg($imgPrefix));
        exec($cmd, $o, $exitCode);

        if ($exitCode !== 0) {
            Log::warning('PDF to image failed', ['file' => $filePath]);

            return '';
        }

        $fullText = '';
        $pages = glob($imgPrefix . '*.png');
        foreach ($pages as $pageImg) {
            $txtFile = $pageImg . '.txt';
            exec(sprintf('tesseract %s %s 2>/dev/null', escapeshellarg($pageImg), escapeshellarg(str_replace('.txt', '', $txtFile))));
            if (file_exists($txtFile)) {
                $fullText .= file_get_contents($txtFile) . "\n";
                @unlink($txtFile);
            }
            @unlink($pageImg);
        }

        return trim($fullText);
    }

    /**
     * Parse a single image with tesseract (fallback).
     */
    protected function parseImage(string $filePath): array
    {
        $outputFile = $filePath . '.txt';

        $cmd = sprintf(
            'tesseract %s %s 2>/dev/null',
            escapeshellarg($filePath),
            escapeshellarg(str_replace('.txt', '', $outputFile))
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || !file_exists($outputFile)) {
            Log::warning('OCR failed', ['file' => $filePath, 'exit' => $exitCode]);

            return $this->buildResult('');
        }

        $rawText = file_get_contents($outputFile);
        @unlink($outputFile);

        return $this->buildResult(trim($rawText));
    }

    private function buildResult(string $rawText): array
    {
        if ($rawText === '') {
            return [
                'merchant' => null,
                'amount' => null,
                'date' => null,
                'raw_text' => '',
            ];
        }

        return [
            'merchant' => $this->extractMerchant($rawText),
            'amount' => $this->extractTotal($rawText),
            'date' => $this->extractDate($rawText),
            'raw_text' => $rawText,
        ];
    }

    private function extractMerchant(string $text): ?string
    {
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || preg_match('/^[\d\s.\-,]+$/', $line)) {
                continue;
            }
            if (preg_match('/^[A-Z][A-Z\s&.]+$/i', $line) && strlen($line) > 3) {
                return $line;
            }
        }

        return null;
    }

    private function extractTotal(string $text): ?float
    {
        if (preg_match('/Total\s*[:=]*\s*[\d.,]+/i', $text, $m)) {
            preg_match('/[\d.,]+/', $m[0], $num);

            return $this->parseAmount($num[0] ?? '0');
        }
        if (preg_match_all('/([\d.,]+)\s*$/', $text, $matches)) {
            $last = end($matches[1]);

            return $this->parseAmount($last);
        }

        return null;
    }

    private function extractDate(string $text): ?string
    {
        if (preg_match('/(\d{2}[-\/]\d{2}[-\/]\d{2,4})/', $text, $m)) {
            return $m[1];
        }

        return null;
    }

    private function parseAmount(string $val): float
    {
        $val = str_replace('.', '', $val);
        $val = str_replace(',', '.', $val);

        return (float) $val;
    }
}
