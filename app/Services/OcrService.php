<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class OcrService
{
    public function parse(string $filePath): array
    {
        // Detect PDF and route to PDF handler
        if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'pdf') {
            return $this->parsePdf($filePath);
        }

        return $this->parseImage($filePath);
    }

    /**
     * Parse a PDF receipt: try text extraction first, then OCR each page.
     */
    public function parsePdf(string $filePath): array
    {
        // 1. Try pdftotext for text-based PDFs
        $textFile = $filePath . '.txt';
        $cmd = sprintf('pdftotext -layout %s %s 2>/dev/null', escapeshellarg($filePath), escapeshellarg($textFile));
        exec($cmd, $o, $exitCode);

        $rawText = '';
        if ($exitCode === 0 && file_exists($textFile)) {
            $rawText = trim(file_get_contents($textFile));
            @unlink($textFile);
        }

        // 2. If no text (scanned PDF), convert pages to images and OCR
        if (mb_strlen($rawText) < 20) {
            $rawText = $this->ocrPdfPages($filePath);
        }

        return $this->buildResult($rawText);
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

        // OCR each generated page image
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
     * Parse a single image (existing behavior).
     */
    private function parseImage(string $filePath): array
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
