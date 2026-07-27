<?php

namespace App\Services;

class OcrService
{
    public function parse(string $filePath): array
    {
        sleep(1);

        $merchants = ['Starbucks', 'Walmart', 'Amazon', 'Target', 'Uber', 'Netflix', 'McDonald\'s', 'Shell'];

        return [
            'merchant' => $merchants[array_rand($merchants)],
            'amount' => rand(10000, 500000),
            'date' => now()->toDateString(),
            'raw_text' => 'Simulated OCR text for '.basename($filePath),
        ];
    }
}
