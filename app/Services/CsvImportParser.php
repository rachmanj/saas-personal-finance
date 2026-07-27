<?php

namespace App\Services;

use League\Csv\Reader;

class CsvImportParser
{
    public function parse(string $filePath, ?int $limit = 50): array
    {
        $csv = Reader::createFromPath($filePath);
        $csv->setHeaderOffset(0);

        $headers = $csv->getHeader();
        $rows = [];

        foreach ($csv->getRecords() as $record) {
            $rows[] = $record;
            if ($limit !== null && count($rows) >= $limit) {
                break;
            }
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'total_rows' => count($csv),
        ];
    }

    public function parseAll(string $filePath): array
    {
        return $this->parse($filePath, null);
    }
}
