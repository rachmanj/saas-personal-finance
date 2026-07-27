<?php

namespace App\Jobs;

use App\Actions\Transactions\CreateTransactionAction;
use App\Enums\ImportStatus;
use App\Models\Import;
use App\Services\CsvImportParser;
use App\Services\ImportDeduplicationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessCsvImport implements ShouldQueue
{
    use Queueable;

    public function __construct(public Import $import)
    {
        $this->onQueue('imports');
    }

    public function handle(
        CsvImportParser $parser,
        ImportDeduplicationService $deduplication,
        CreateTransactionAction $createTransaction
    ): void {
        $this->import->update(['status' => ImportStatus::Processing]);

        $this->import->load('account');

        try {
            $filePath = Storage::disk('imports')->path($this->import->file_path);
            $parsed = $parser->parseAll($filePath);
            $mapping = $this->import->column_mapping ?? [];
            $selectedRows = $mapping['selected_rows'] ?? null;

            $imported = 0;
            $skipped = 0;
            $transactionIds = [];

            foreach ($parsed['rows'] as $index => $row) {
                if ($selectedRows !== null && ! in_array($index, $selectedRows, true)) {
                    continue;
                }

                $date = $row[$mapping['date'] ?? ''] ?? null;
                $description = $row[$mapping['description'] ?? ''] ?? '';
                $amountRaw = $row[$mapping['amount'] ?? ''] ?? 0;
                $amount = (float) $amountRaw;

                if (! $date) {
                    $skipped++;

                    continue;
                }

                $type = $amount < 0 ? 'expense' : 'income';
                $absAmount = abs($amount);

                if ($deduplication->isDuplicate($this->import->account_id, $date, $absAmount, $description)) {
                    $skipped++;

                    continue;
                }

                $transaction = $createTransaction->execute([
                    'team_id' => $this->import->team_id,
                    'user_id' => $this->import->user_id,
                    'account_id' => $this->import->account_id,
                    'type' => $type,
                    'amount' => $absAmount,
                    'currency' => $this->import->account->currency,
                    'description' => $description,
                    'transaction_date' => $date,
                    'source' => 'import',
                ]);

                $transactionIds[] = $transaction->id;
                $imported++;
            }

            $this->import->update([
                'status' => ImportStatus::Completed,
                'total_rows' => $parsed['total_rows'],
                'imported_rows' => $imported,
                'skipped_rows' => $skipped,
            ]);

            AutoCategorizeImport::dispatch($this->import, $transactionIds);
        } catch (Throwable $e) {
            $this->import->update([
                'status' => ImportStatus::Failed,
                'error_log' => ['message' => $e->getMessage()],
            ]);
        }
    }
}
