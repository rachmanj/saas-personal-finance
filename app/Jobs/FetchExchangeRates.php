<?php

namespace App\Jobs;

use App\Models\ExchangeRate;
use App\Services\ExchangeRateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FetchExchangeRates implements ShouldQueue
{
    use Queueable;

    /**
     * Active currencies to fetch rates for.
     */
    private const ACTIVE_CURRENCIES = ['USD', 'EUR', 'GBP', 'JPY', 'IDR', 'AUD', 'CAD', 'CHF', 'CNY', 'SGD'];

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(ExchangeRateService $service): void
    {
        $today = now()->toDateString();

        foreach (self::ACTIVE_CURRENCIES as $base) {
            $rates = $service->fetchRates($base);

            foreach ($rates as $target => $rate) {
                if ($base === $target) {
                    continue;
                }

                ExchangeRate::updateOrCreate(
                    [
                        'base_currency' => $base,
                        'target_currency' => $target,
                        'rate_date' => $today,
                    ],
                    [
                        'rate' => $rate,
                    ]
                );
            }
        }
    }
}