<?php

namespace Tests\Unit\Services;

use App\Services\OfxImportParser;
use Tests\TestCase;

class OfxImportParserTest extends TestCase
{
    private string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturePath = base_path('tests/Fixtures/sample.ofx');
    }

    public function test_parsing_ofx_file_returns_transactions(): void
    {
        $parser = new OfxImportParser;

        $result = $parser->parse($this->fixturePath);

        $this->assertArrayHasKey('account_info', $result);
        $this->assertArrayHasKey('transactions', $result);
        $this->assertArrayHasKey('total_rows', $result);

        $this->assertEquals('9876543210', $result['account_info']['account_id']);
        $this->assertEquals('123456789', $result['account_info']['bank_id']);
        $this->assertEquals('CHECKING', $result['account_info']['account_type']);

        $this->assertCount(4, $result['transactions']);
        $this->assertEquals(4, $result['total_rows']);
    }

    public function test_date_format_conversion(): void
    {
        $parser = new OfxImportParser;

        $result = $parser->parse($this->fixturePath);

        $this->assertEquals('2024-01-15', $result['transactions'][0]['date']);
        $this->assertEquals('2024-01-16', $result['transactions'][1]['date']);
    }

    public function test_amount_parsing(): void
    {
        $parser = new OfxImportParser;

        $result = $parser->parse($this->fixturePath);

        $this->assertEquals('DEBIT', $result['transactions'][0]['type']);
        $this->assertEquals(-50.00, $result['transactions'][0]['amount']);
        $this->assertEquals('Grocery Store', $result['transactions'][0]['description']);
        $this->assertEquals('Weekly groceries', $result['transactions'][0]['memo']);

        $this->assertEquals('CREDIT', $result['transactions'][1]['type']);
        $this->assertEquals(2500.00, $result['transactions'][1]['amount']);
    }
}
