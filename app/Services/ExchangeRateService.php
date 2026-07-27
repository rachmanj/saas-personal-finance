<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ExchangeRateService
{
    /**
     * Fetch live rates from exchangerate-api.com for a base currency.
     * Cached for 12 hours via Redis.
     *
     * STUB: Returns realistic rates without calling external API.
     * Replace with HTTP call to https://api.exchangerate-api.com/v4/latest/{base}
     * when EXCHANGERATE_API_KEY is configured.
     */
    public function fetchRates(string $baseCurrency): array
    {
        $cacheKey = "fx:{$baseCurrency}";

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($baseCurrency) {
            return $this->getStubRates($baseCurrency);
        });
    }

    /**
     * Get a specific rate for a currency pair.
     */
    public function rateFor(string $from, string $to): ?float
    {
        if ($from === $to) {
            return 1.0;
        }

        $rates = $this->fetchRates($from);

        return $rates[$to] ?? null;
    }

    /**
     * Realistic stub rates for common currencies.
     */
    private function getStubRates(string $base): array
    {
        $rates = [
            'USD' => 1.0,
            'EUR' => 0.91,
            'GBP' => 0.77,
            'JPY' => 149.50,
            'IDR' => 15750.0,
            'AUD' => 1.52,
            'CAD' => 1.36,
            'CHF' => 0.88,
            'CNY' => 7.24,
            'SGD' => 1.34,
            'MYR' => 4.68,
            'THB' => 35.80,
            'VND' => 24850.0,
            'PHP' => 56.20,
            'INR' => 83.50,
        ];

        if ($base === 'USD') {
            return $rates;
        }

        // Convert from base currency to other currencies
        $baseRate = $rates[$base] ?? 1.0;
        $converted = [];
        foreach ($rates as $currency => $rate) {
            $converted[$currency] = $rate / $baseRate;
        }

        return $converted;
    }
}