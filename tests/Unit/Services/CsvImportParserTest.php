<?php

namespace Tests\Unit\Services;

use App\Services\CsvImportParser;
use Tests\TestCase;

class CsvImportParserTest extends TestCase
{
    private string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturePath = base_path('tests/Fixtures/sample.csv');
    }

    public function test_parsing_csv_returns_headers_and_rows(): void
    {
        $parser = new CsvImportParser;

        $result = $parser->parse($this->fixturePath);

        $this->assertArrayHasKey('headers', $result);
        $this->assertArrayHasKey('rows', $result);
        $this->assertArrayHasKey('total_rows', $result);

        $this->assertEquals(['Date', 'Description', 'Amount', 'Category'], $result['headers']);
        $this->assertCount(8, $result['rows']);
        $this->assertEquals(8, $result['total_rows']);
        $this->assertEquals('Grocery Store', $result['rows'][0]['Description']);
        $this->assertEquals('-50.00', $result['rows'][0]['Amount']);
    }

    public function test_first_50_rows_limit(): void
    {
        $tempPath = sys_get_temp_dir().'/csv_import_test_'.uniqid().'.csv';

        $handle = fopen($tempPath, 'w');
        fputcsv($handle, ['Date', 'Description', 'Amount']);
        for ($i = 1; $i <= 60; $i++) {
            $date = sprintf('2024-01-%02d', (($i - 1) % 28) + 1);
            fputcsv($handle, [$date, "Transaction {$i}", '-10.00']);
        }
        fclose($handle);

        $parser = new CsvImportParser;
        $result = $parser->parse($tempPath);

        $this->assertCount(50, $result['rows']);
        $this->assertEquals(60, $result['total_rows']);

        unlink($tempPath);
    }
}
