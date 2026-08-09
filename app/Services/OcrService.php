<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class OcrService
{
    public function parse(string $filePath): array
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
            return [
                'merchant' => null,
                'amount' => null,
                'date' => null,
                'raw_text' => '',
            ];
        }

        $rawText = file_get_contents($outputFile);
        @unlink($outputFile);

        return [
            'merchant' => $this->extractMerchant($rawText),
            'amount' => $this->extractTotal($rawText),
            'date' => $this->extractDate($rawText),
            'raw_text' => trim($rawText),
        ];
    }

    private function extractMerchant(string $text): ?string
    {
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $line = trim($line);
            // Skip empty lines and number-only lines
            if (empty($line) || preg_match('/^[\d\s.\-,]+$/', $line)) {
                continue;
            }
            // First meaningful line is usually the merchant name
            if (preg_match('/^[A-Z][A-Z\s&.]+$/i', $line) && strlen($line) > 3) {
                return $line;
            }
        }
        return null;
    }

    private function extractTotal(string $text): ?float
    {
        // Look for "Total" line
        if (preg_match('/Total\s*[:=]*\s*[\d.,]+/i', $text, $m)) {
            preg_match('/[\d.,]+/', $m[0], $num);
            return $this->parseAmount($num[0] ?? '0');
        }
        // Look for last amount in receipt
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
        // Indonesian format: 9.000 or 9,000
        $val = str_replace('.', '', $val);
        $val = str_replace(',', '.', $val);
        return (float) $val;
    }
}
