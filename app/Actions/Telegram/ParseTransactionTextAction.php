<?php

namespace App\Actions\Telegram;

use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;

class ParseTransactionTextAction
{
    /**
     * Parse a natural-language transaction message into structured data.
     * Tries Gemini AI first if configured, falls back to regex parsing.
     *
     * @param string $text Raw message like "makan siang 50rb", "gaji 5 juta"
     *
     * @return array{amount: ?int, description: string, type: string, category_suggestion: ?string, date: ?string, merchant: ?string, error: ?string}
     */
    public function execute(string $text): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));

        // Try Gemini AI first if API key is configured
        $geminiService = app(GeminiService::class);
        if ($geminiService->isConfigured()) {
            try {
                $result = $geminiService->parseTransactionText($text);
                if ($result['amount'] !== null) {
                    return $result;
                }
                Log::info('Gemini returned no amount, falling back to regex', ['text' => $text]);
            } catch (\Throwable $e) {
                Log::info('Gemini parse failed, falling back to regex', [
                    'text' => $text,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // --- Regex fallback (existing logic) ---

        // Extract amount
        $amountParser = new ParseIndonesianAmountAction;
        $amount = $amountParser->execute($text);

        // Remove the amount portion from text to get description
        $description = $this->extractDescription($text);
        $description = trim(preg_replace('/\s+/', ' ', $description));

        if ($amount === null) {
            return [
                'amount' => null,
                'description' => $description ?: $text,
                'type' => 'expense',
                'category_suggestion' => null,
                'date' => $this->extractDate($text),
                'merchant' => null,
                'error' => 'no_amount',
            ];
        }

        // Determine type
        $type = $this->detectType($text);

        // Category suggestion
        $categorySuggestion = $this->suggestCategory($description);

        return [
            'amount' => $amount,
            'description' => $description,
            'type' => $type,
            'category_suggestion' => $categorySuggestion,
            'date' => $this->extractDate($text),
            'merchant' => null,
            'error' => null,
        ];
    }

    /**
     * Extract description by removing amount tokens from the text.
     */
    private function extractDescription(string $text): string
    {
        // Remove date prefixes like "Tanggal 10 Agt 2026", "10 Agustus 2026", etc.
        $monthAbb = '(?:Jan(?:uari)?|Feb(?:ruari)?|Mar(?:et)?|Apr(?:il)?|Mei|Jun(?:i)?|Jul(?:i)?|Ag(?:u(?:stus)?)?t?|Sep(?:tember)?|Okt(?:ober)?|Nov(?:ember)?|Des(?:ember)?)';
        $text = preg_replace('/\bTanggal\s+\d{1,2}\s+' . $monthAbb . '\s+\d{4}\b/i', '', $text);
        $text = preg_replace('/\b\d{1,2}\s+' . $monthAbb . '\s+\d{4}\b/i', '', $text);

        $textLower = mb_strtolower($text);

        // Remove amount+optional suffix patterns
        // e.g., "50rb", "5 juta", "1.5jt", "50000"
        $patterns = [
            '/\d+(?:[.,]\d+)?\s*(?:juta|jt)\b/i',
            '/\d+(?:[.,]\d+)?\s*(?:ribu|rb)\b/i',
            '/\d+(?:[.,]\d+)?\s*k\b/i',
            // Plain numbers: digits with optional dots/commas
            '/\d[\d.,]*\d+/',
        ];

        foreach ($patterns as $pattern) {
            $text = preg_replace($pattern, '', $text, 1);
        }

        // Remove the word "dari" and "pake" + the rest as account hints (optional)
        $text = preg_replace('/\b(?:dari|pake|pakai)\s+\S+/i', '', $text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /**
     * Detect transaction type from the text content.
     */
    private function detectType(string $text): string
    {
        $textLower = mb_strtolower($text);

        $incomeKeywords = [
            'gaji', 'bonus', 'dapat', 'terima', 'masuk', 'jual',
            'penghasilan', 'transfer masuk', 'hadiah', 'uang masuk',
            'pendapatan', 'pemasukan', 'dikasih', 'diberi',
        ];

        $expenseKeywords = [
            'beli', 'bayar', 'makan', 'bensin', 'transport', 'listrik',
            'air', 'pulsa', 'sewa', 'cicilan', 'ngopi', 'kopi',
            'belanja', 'topup', 'top up', 'isi', 'parkir', 'tol',
            'laundry', 'obat', 'dokter', 'rumah sakit',
            'langganan', 'subscription', 'internet', 'wifi',
            'pajak', 'bpjs', 'asuransi', 'sumbangan', 'donasi',
            'jajan', 'nonton', 'bioskop', 'main', 'game',
            'servis', 'service', 'perbaikan', 'benerin',
        ];

        foreach ($incomeKeywords as $keyword) {
            if (stripos($textLower, $keyword) !== false) {
                return 'income';
            }
        }

        foreach ($expenseKeywords as $keyword) {
            if (stripos($textLower, $keyword) !== false) {
                return 'expense';
            }
        }

        // Default to expense
        return 'expense';
    }

    /**
     * Suggest a category based on description keywords.
     */
    private function suggestCategory(string $description): ?string
    {
        $lower = mb_strtolower($description);

        $categoryMap = [
            'makan|minum|ngopi|kopi|sarapan|siang|malam|restoran|warung|cafe|jajan' => 'Makanan & Minuman',
            'bensin|bbm|pertamina|shell|solar' => 'Bensin',
            'transport|gojek|grab|ojek|taxi|bus|kereta|tol|parkir' => 'Transportasi',
            'listrik|pln|token' => 'Listrik',
            'air|pdam' => 'Air',
            'pulsa|paket|data|telkomsel|xl|indosat|tri|smartfren' => 'Pulsa & Internet',
            'sewa|kontrakan|kos|apartemen' => 'Sewa',
            'gaji|bonus|penghasilan' => 'Gaji',
            'belanja|minimarket|indomaret|alfamart|superindo' => 'Belanja',
            'obat|dokter|apotek|rumah sakit' => 'Kesehatan',
            'langganan|subscription|spotify|netflix|disney' => 'Langganan',
            'game|steam|playstation|xbox|nintendo' => 'Hiburan',
        ];

        foreach ($categoryMap as $pattern => $category) {
            if (preg_match("/{$pattern}/i", $lower)) {
                return $category;
            }
        }

        return null;
    }

    /**
     * Extract a date from text if present.
     * Supports: "09-08-26", "9 Agustus 2026", "09/08", etc.
     */
    private function extractDate(string $text): ?string
    {
        // DD-MM-YY or DD-MM-YYYY
        if (preg_match('/(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4})/', $text, $m)) {
            $d = \DateTime::createFromFormat('d-m-y', str_replace('/', '-', $m[1]));
            if (! $d) $d = \DateTime::createFromFormat('d-m-Y', str_replace('/', '-', $m[1]));
            if ($d) return $d->format('Y-m-d');
        }

        // "9 Agustus 2026" or "9 Agt 2026" or "9 Agustus"
        $idMonths = [
            'januari','februari','maret','april','mei','juni','juli','agustus','september','oktober','november','desember',
        ];
        $idMonthAbbreviations = [
            'jan','feb','mar','apr','mei','jun','jul','agt','sep','okt','nov','des',
        ];
        $allMonths = array_merge($idMonths, $idMonthAbbreviations);
        $pattern = '/(\d{1,2})\s*(' . implode('|', $allMonths) . ')(?:\s*(\d{4}))?/i';
        if (preg_match($pattern, $text, $m)) {
            $day = (int) $m[1];
            $monthName = strtolower($m[2]);
            // Try full name first, then abbreviation
            $monthIndex = array_search($monthName, $idMonths);
            if ($monthIndex === false) {
                $monthIndex = array_search($monthName, $idMonthAbbreviations);
            }
            $month = $monthIndex !== false ? $monthIndex + 1 : 0;
            $year = !empty($m[3]) ? (int) $m[3] : (int) date('Y');
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        return null;
    }
}
