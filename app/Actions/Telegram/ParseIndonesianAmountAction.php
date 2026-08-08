<?php

namespace App\Actions\Telegram;

class ParseIndonesianAmountAction
{
    /**
     * Parse an Indonesian-formatted amount string into an integer.
     *
     * Supported formats:
     * - "50rb" / "50ribu" → 50000
     * - "1.5jt" / "1,5juta" → 1500000
     * - "5 juta" → 5000000
     * - "100k" → 100000
     * - "50000" / "50.000" → 50000
     *
     * Returns null if no valid amount found.
     */
    public function execute(string $text): ?int
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        $textLower = mb_strtolower($text);

        // Detect suffix and multiplier (order matters: juta/jt before rb/ribu/k)
        $multiplier = 1;

        // Match: number[optional space]suffix
        // Use lookbehind for digit OR word boundary for "5 juta" (space-separated)
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(juta|jt)\b/i', $textLower, $m)) {
            $multiplier = 1_000_000;
        } elseif (preg_match('/(\d+(?:[.,]\d+)?)\s*(ribu|rb)\b/i', $textLower, $m)) {
            $multiplier = 1_000;
        } elseif (preg_match('/(\d+(?:[.,]\d+)?)\s*k\b/i', $textLower, $m)) {
            $multiplier = 1_000;
        } else {
            // Plain number (no suffix) — match consecutive digits, dots, or commas
            if (preg_match('/(\d[\d.,]*\d)/', $textLower, $m)) {
                $multiplier = 1;
            } else {
                return null;
            }
        }

        $numericStr = $m[1];
        $clean = $this->normalizeNumeric($numericStr);

        if (is_numeric($clean) && $clean > 0) {
            return (int) round((float) $clean * $multiplier);
        }

        return null;
    }

    /**
     * Normalize an Indonesian numeric string to a machine-readable float string.
     * "1.500.000" → "1500000"
     * "2,5" → "2.5"
     * "1.500,75" → "1500.75"
     */
    private function normalizeNumeric(string $numericStr): string
    {
        $hasComma = str_contains($numericStr, ',');
        $hasDot = str_contains($numericStr, '.');

        if ($hasComma && $hasDot) {
            // Both present: dot is thousand separator, comma is decimal
            $numericStr = str_replace('.', '', $numericStr);
            $numericStr = str_replace(',', '.', $numericStr);
        } elseif ($hasComma) {
            // Only comma: could be "2,5" (decimal) or "1,500" (thousands)
            $parts = explode(',', $numericStr);
            if (count($parts) === 2 && strlen($parts[0]) <= 3 && strlen($parts[1]) === 3) {
                // "1,500" → thousands separator
                $numericStr = str_replace(',', '', $numericStr);
            } else {
                // "2,5" → decimal
                $numericStr = str_replace(',', '.', $numericStr);
            }
        }
        // Dots only: remove thousand separators if present
        if (str_contains($numericStr, '.')) {
            $parts = explode('.', $numericStr);
            $lastPart = end($parts);
            if (count($parts) > 2 || strlen($lastPart) === 3) {
                $numericStr = str_replace('.', '', $numericStr);
            }
        }

        return $numericStr;
    }
}
