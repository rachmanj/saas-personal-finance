<?php

namespace Tests\Unit\Actions;

use App\Actions\Telegram\ParseTransactionTextAction;
use App\Services\AiParserService;
use Tests\TestCase;

class ParseTransactionTextAiTest extends TestCase
{
    private ParseTransactionTextAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new ParseTransactionTextAction;
    }

    private function aiMock(array $result, bool $configured = true): AiParserService
    {
        $mock = $this->createMock(AiParserService::class);
        $mock->method('isConfigured')->willReturn($configured);
        $mock->method('parseTransactionText')->willReturn($result);

        return $mock;
    }

    public function test_it_uses_ai_when_configured(): void
    {
        $mock = $this->aiMock([
            'amount' => 452500,
            'description' => 'Bayar BPJS Sofie',
            'type' => 'expense',
            'category_suggestion' => 'Kesehatan',
            'date' => '2026-08-10',
            'merchant' => 'BPJS',
            'error' => null,
        ]);

        $this->app->instance(AiParserService::class, $mock);

        $result = $this->action->execute('Tanggal 10 Agt 2026 Bayar BPJS Sofie 452500');

        $this->assertEquals(452500, $result['amount']);
        $this->assertEquals('Bayar BPJS Sofie', $result['description']);
        $this->assertEquals('expense', $result['type']);
        $this->assertEquals('Kesehatan', $result['category_suggestion']);
        $this->assertEquals('2026-08-10', $result['date']);
        $this->assertEquals('BPJS', $result['merchant']);
        $this->assertNull($result['error']);
    }

    public function test_it_falls_back_to_regex_when_ai_fails(): void
    {
        $mock = $this->createMock(AiParserService::class);
        $mock->method('isConfigured')->willReturn(true);
        $mock->method('parseTransactionText')
            ->willThrowException(new \RuntimeException('API error'));

        $this->app->instance(AiParserService::class, $mock);

        $result = $this->action->execute('makan siang 50rb');

        $this->assertEquals(50000, $result['amount']);
        $this->assertStringContainsString('makan siang', $result['description']);
        $this->assertEquals('expense', $result['type']);
    }

    public function test_it_falls_back_when_ai_returns_no_amount(): void
    {
        $mock = $this->aiMock([
            'amount' => null,
            'description' => 'makan siang',
            'type' => 'expense',
            'category_suggestion' => null,
            'date' => null,
            'merchant' => null,
            'error' => 'no_amount',
        ]);

        $this->app->instance(AiParserService::class, $mock);

        $result = $this->action->execute('makan siang 50rb');

        $this->assertEquals(50000, $result['amount']);
        $this->assertEquals('expense', $result['type']);
    }

    public function test_it_uses_regex_when_ai_not_configured(): void
    {
        $mock = $this->createMock(AiParserService::class);
        $mock->method('isConfigured')->willReturn(false);
        $mock->expects($this->never())->method('parseTransactionText');

        $this->app->instance(AiParserService::class, $mock);

        $result = $this->action->execute('makan siang 50rb');

        $this->assertEquals(50000, $result['amount']);
        $this->assertStringContainsString('makan siang', $result['description']);
        $this->assertEquals('expense', $result['type']);
    }

    public function test_regex_fallback_includes_date_and_merchant_fields(): void
    {
        // No API key set → uses regex
        $result = $this->action->execute('makan siang 50rb');

        $this->assertArrayHasKey('date', $result);
        $this->assertArrayHasKey('merchant', $result);
    }

    public function test_it_parses_bpjs_message_with_ai(): void
    {
        $mock = $this->aiMock([
            'amount' => 452500,
            'description' => 'Bayar BPJS Sofie',
            'type' => 'expense',
            'category_suggestion' => 'Kesehatan',
            'date' => '2026-08-10',
            'merchant' => 'BPJS',
            'error' => null,
        ]);

        $this->app->instance(AiParserService::class, $mock);

        $result = $this->action->execute('Tanggal 10 Agt 2026 Bayar BPJS Sofie 452500');

        $this->assertEquals(452500, $result['amount']);
        $this->assertEquals('2026-08-10', $result['date']);
        $this->assertEquals('Kesehatan', $result['category_suggestion']);
        $this->assertEquals('BPJS', $result['merchant']);
        $this->assertEquals('expense', $result['type']);
    }
}
