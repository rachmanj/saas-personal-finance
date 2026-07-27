<?php

namespace Tests\Unit\Services;

use App\Models\ExchangeRate;
use App\Services\CurrencyConverterService;
use App\Services\ExchangeRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyConverterServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CurrencyConverterService $converter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->converter = new CurrencyConverterService(
            new ExchangeRateService
        );
    }

    public function test_rate_for_same_currency_returns_one(): void
    {
        $rate = $this->converter->rateFor('USD', 'USD');

        $this->assertEquals(1.0, $rate);
    }

    public function test_rate_for_returns_rate_from_database_first(): void
    {
        ExchangeRate::create([
            'base_currency' => 'USD',
            'target_currency' => 'EUR',
            'rate' => 2.500000,
            'rate_date' => '2026-07-27',
        ]);

        $rate = $this->converter->rateFor('USD', 'EUR', '2026-07-27');

        $this->assertEquals(2.5, $rate);
    }

    public function test_rate_for_uses_reverse_lookup(): void
    {
        ExchangeRate::create([
            'base_currency' => 'EUR',
            'target_currency' => 'USD',
            'rate' => 1.100000,
            'rate_date' => '2026-07-27',
        ]);

        $rate = $this->converter->rateFor('USD', 'EUR', '2026-07-27');

        $this->assertEquals(1.0 / 1.1, $rate);
    }

    public function test_rate_for_falls_back_to_live_service(): void
    {
        // No ExchangeRate record exists, so falls back to stub service
        $rate = $this->converter->rateFor('USD', 'EUR');

        $this->assertGreaterThan(0, $rate);
        $this->assertNotEquals(1.0, $rate); // USD->EUR is not 1.0
    }

    public function test_convert_calculates_correctly(): void
    {
        ExchangeRate::create([
            'base_currency' => 'USD',
            'target_currency' => 'EUR',
            'rate' => 0.910000,
            'rate_date' => '2026-07-27',
        ]);

        $converted = $this->converter->convert(100.0, 'USD', 'EUR', '2026-07-27');

        $this->assertEquals(91.0, $converted);
    }
}