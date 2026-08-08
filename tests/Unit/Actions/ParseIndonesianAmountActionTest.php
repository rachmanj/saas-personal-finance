<?php

namespace Tests\Unit\Actions;

use App\Actions\Telegram\ParseIndonesianAmountAction;
use Tests\TestCase;

class ParseIndonesianAmountActionTest extends TestCase
{
    private ParseIndonesianAmountAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new ParseIndonesianAmountAction;
    }

    public function test_it_parses_rb_suffix(): void
    {
        $this->assertEquals(50000, $this->action->execute('50rb'));
        $this->assertEquals(100000, $this->action->execute('100rb'));
        $this->assertEquals(75000, $this->action->execute('75rb'));
        $this->assertEquals(1000, $this->action->execute('1rb'));
    }

    public function test_it_parses_ribu_suffix(): void
    {
        $this->assertEquals(50000, $this->action->execute('50 ribu'));
        $this->assertEquals(100000, $this->action->execute('100ribu'));
        $this->assertEquals(200000, $this->action->execute('200 ribu'));
    }

    public function test_it_parses_jt_suffix(): void
    {
        $this->assertEquals(1000000, $this->action->execute('1jt'));
        $this->assertEquals(5000000, $this->action->execute('5jt'));
        $this->assertEquals(1500000, $this->action->execute('1.5jt'));
        $this->assertEquals(2500000, $this->action->execute('2,5jt'));
        $this->assertEquals(750000, $this->action->execute('0.75jt'));
    }

    public function test_it_parses_juta_suffix(): void
    {
        $this->assertEquals(1000000, $this->action->execute('1 juta'));
        $this->assertEquals(5000000, $this->action->execute('5juta'));
        $this->assertEquals(1500000, $this->action->execute('1.5 juta'));
        $this->assertEquals(10000000, $this->action->execute('10 juta'));
    }

    public function test_it_parses_k_suffix(): void
    {
        $this->assertEquals(100000, $this->action->execute('100k'));
        $this->assertEquals(50000, $this->action->execute('50k'));
        $this->assertEquals(150000, $this->action->execute('150k'));
    }

    public function test_it_parses_plain_numbers(): void
    {
        $this->assertEquals(50000, $this->action->execute('50000'));
        $this->assertEquals(100000, $this->action->execute('100000'));
        $this->assertEquals(1500000, $this->action->execute('1500000'));
        $this->assertEquals(75000, $this->action->execute('75000'));
    }

    public function test_it_parses_numbers_with_dot_separators(): void
    {
        $this->assertEquals(50000, $this->action->execute('50.000'));
        $this->assertEquals(100000, $this->action->execute('100.000'));
        $this->assertEquals(1500000, $this->action->execute('1.500.000'));
    }

    public function test_it_returns_null_for_invalid_input(): void
    {
        $this->assertNull($this->action->execute(''));
        $this->assertNull($this->action->execute('makan siang'));
        $this->assertNull($this->action->execute('no amount'));
        $this->assertNull($this->action->execute('abc'));
    }

    public function test_it_is_case_insensitive(): void
    {
        $this->assertEquals(50000, $this->action->execute('50RB'));
        $this->assertEquals(1000000, $this->action->execute('1JT'));
        $this->assertEquals(5000000, $this->action->execute('5 JUTA'));
        $this->assertEquals(100000, $this->action->execute('100K'));
    }

    public function test_it_handles_amount_at_end_of_text(): void
    {
        $this->assertEquals(50000, $this->action->execute('makan siang 50rb'));
        $this->assertEquals(1000000, $this->action->execute('gaji 1jt'));
        $this->assertEquals(150000, $this->action->execute('beli bensin 150rb'));
    }

    public function test_it_handles_amount_at_start_of_text(): void
    {
        $this->assertEquals(50000, $this->action->execute('50rb makan siang'));
        $this->assertEquals(1000000, $this->action->execute('1jt gaji masuk'));
    }

    public function test_it_handles_amount_in_middle_of_text(): void
    {
        $this->assertEquals(50000, $this->action->execute('makan 50rb siang'));
    }
}
