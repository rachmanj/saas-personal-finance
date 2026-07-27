<?php

namespace App\Services;

class CurrencyConverterService
{
    /**
     * Get the exchange rate between two currencies for a given date.
     *
     * STUB: Returns 1.0 for now. Will be replaced with real API (exchangerate-api.com) in Phase 6.
     */
    public function rateFor(string $fromCurrency, string $toCurrency, ?string $date = null): float
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        return 1.0;
    }
}
