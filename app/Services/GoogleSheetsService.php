<?php

namespace App\Services;

class GoogleSheetsService
{
    /**
     * @param  array<int, array<string, mixed>>  $transactions
     * @return array{synced: int, spreadsheet_id: string}
     */
    public function syncTransactions(string $spreadsheetId, array $transactions): array
    {
        return ['synced' => count($transactions), 'spreadsheet_id' => $spreadsheetId];
    }
}
