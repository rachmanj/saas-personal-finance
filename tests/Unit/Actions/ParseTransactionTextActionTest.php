<?php

namespace Tests\Unit\Actions;

use App\Actions\Telegram\ParseTransactionTextAction;
use Tests\TestCase;

class ParseTransactionTextActionTest extends TestCase
{
    private ParseTransactionTextAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new ParseTransactionTextAction;
    }

    public function test_it_parses_expense_with_rb(): void
    {
        $result = $this->action->execute('makan siang 50rb');

        $this->assertEquals(50000, $result['amount']);
        $this->assertStringContainsString('makan siang', $result['description']);
        $this->assertEquals('expense', $result['type']);
    }

    public function test_it_parses_income_with_jt(): void
    {
        $result = $this->action->execute('gaji 5jt');

        $this->assertEquals(5000000, $result['amount']);
        $this->assertStringContainsString('gaji', $result['description']);
        $this->assertEquals('income', $result['type']);
    }

    public function test_it_parses_income_with_juta(): void
    {
        $result = $this->action->execute('gaji 5 juta');

        $this->assertEquals(5000000, $result['amount']);
        $this->assertEquals('income', $result['type']);
    }

    public function test_it_parses_expense_with_k(): void
    {
        $result = $this->action->execute('bensin 100k');

        $this->assertEquals(100000, $result['amount']);
        $this->assertEquals('expense', $result['type']);
    }

    public function test_it_defaults_to_expense(): void
    {
        $result = $this->action->execute('makan 50rb');

        $this->assertEquals('expense', $result['type']);
        $this->assertEquals(50000, $result['amount']);
    }

    public function test_it_detects_income_keywords(): void
    {
        $incomeKeywords = ['gaji', 'bonus', 'dapat', 'terima', 'masuk', 'jual', 'penghasilan', 'transfer masuk'];

        foreach ($incomeKeywords as $keyword) {
            $result = $this->action->execute("{$keyword} 50rb");
            $this->assertEquals('income', $result['type'], "Failed for keyword: {$keyword}");
        }
    }

    public function test_it_detects_expense_keywords(): void
    {
        $expenseKeywords = ['beli', 'bayar', 'makan', 'bensin', 'transport', 'listrik', 'air', 'pulsa', 'sewa', 'cicilan'];

        foreach ($expenseKeywords as $keyword) {
            $result = $this->action->execute("{$keyword} 50rb");
            $this->assertEquals('expense', $result['type'], "Failed for keyword: {$keyword}");
        }
    }

    public function test_it_returns_description_without_amount(): void
    {
        $result = $this->action->execute('makan siang di restoran 50rb');

        $this->assertEquals(50000, $result['amount']);
        $this->assertEquals('makan siang di restoran', $result['description']);
    }

    public function test_it_returns_null_for_no_amount(): void
    {
        $result = $this->action->execute('makan siang');

        $this->assertNull($result['amount']);
        $this->assertEquals('no_amount', $result['error'] ?? null);
    }

    public function test_it_returns_plain_number_amount(): void
    {
        $result = $this->action->execute('belanja 50000');

        $this->assertEquals(50000, $result['amount']);
        $this->assertEquals('expense', $result['type']);
    }

    public function test_it_handles_income_at_start(): void
    {
        $result = $this->action->execute('dapat gaji 5 juta');

        $this->assertEquals(5000000, $result['amount']);
        $this->assertEquals('income', $result['type']);
    }

    public function test_it_handles_amount_first_then_description(): void
    {
        $result = $this->action->execute('50rb makan siang');

        $this->assertEquals(50000, $result['amount']);
        $this->assertEquals('makan siang', $result['description']);
    }

    public function test_it_returns_category_suggestion(): void
    {
        $result = $this->action->execute('makan siang 50rb');

        $this->assertArrayHasKey('category_suggestion', $result);
    }

    public function test_it_strips_extra_spaces_from_description(): void
    {
        $result = $this->action->execute('  makan   siang   50rb  ');

        $this->assertEquals('makan siang', $result['description']);
        $this->assertEquals(50000, $result['amount']);
    }
}
