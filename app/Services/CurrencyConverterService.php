<?php

namespace App\Services;

use App\Models\ExchangeRate;

class CurrencyConverterService
{
    public function __construct(
        private ?ExchangeRateService $exchangeRateService = null
    ) {}

    /**
     * Get the exchange rate between two currencies for a given date.
     * Checks ExchangeRate table first (historical), falls back to ExchangeRateService (live).
     */
    public function rateFor(string $fromCurrency, string $toCurrency, ?string $date = null): float
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $date = $date ?? now()->toDateString();

        // Check ExchangeRate table first (historical)
        $exchangeRate = ExchangeRate::query()
            ->where('base_currency', $fromCurrency)
            ->where('target_currency', $toCurrency)
            ->where('rate_date', $date)
            ->first();

        if ($exchangeRate) {
            return (float) $exchangeRate->rate;
        }

        // Try reverse lookup
        $reverseRate = ExchangeRate::query()
            ->where('base_currency', $toCurrency)
            ->where('target_currency', $fromCurrency)
            ->where('rate_date', $date)
            ->first();

        if ($reverseRate) {
            return 1.0 / (float) $reverseRate->rate;
        }

        // Fall back to live service
        $rate = $this->exchangeRateService?->rateFor($fromCurrency, $toCurrency);

        return $rate ?? 1.0;
    }

    /**
     * Convert an amount from one currency to another.
     */
    public function convert(float $amount, string $fromCurrency, string $toCurrency, ?string $date = null): float
    {
        $rate = $this->rateFor($fromCurrency, $toCurrency, $date);

        return $amount * $rate;
    }
}