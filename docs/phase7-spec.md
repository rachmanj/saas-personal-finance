# Phase 7 — Bank Import (CSV/OFX) — Backend Spec

## Context
- Project: saas-personal-finance, Laravel 13, PHP 8.5, MySQL 8.4
- 66 tests green, commit 745bfc7
- Phase 1-5 complete: Auth, Teams, Accounts, Categories, Tags, Transactions, Dashboard, OCR, Voice
- Important: `league/csv` is NOT yet installed. Install it with: `composer require league/csv`

## Existing Patterns to Follow
- All models use `BelongsToTeam` trait (global team scope)
- API responses use `{ data, message, errors, meta }` envelope
- Controllers under `app/Http/Controllers/Api/`
- Actions under `app/Actions/`
- Services under `app/Services/`
- Jobs under `app/Jobs/`
- FormRequests under `app/Http/Requests/`
- Enums under `app/Enums/`
- Routes in `routes/api.php` under `auth:sanctum` middleware

## IMPORTANT: PHP 8.5 Compatibility
- PHP 8.5 is strict. No implicit nullable types. Use `?type` explicitly.
- `league/csv` must be compatible with PHP 8.5

## Task List — Backend

### 1. Install league/csv
```bash
composer require league/csv
```

### 2. Create Import Model + Migration + Factory + Controller
```bash
php artisan make:model Import -mfc --api
```

Migration columns:
- `team_id` (foreignId, constrained, cascadeOnDelete)
- `user_id` (foreignId, constrained)
- `account_id` (foreignId, constrained, cascadeOnDelete)
- `file_path` (string)
- `file_type` (enum: 'csv', 'ofx')
- `status` (enum: 'pending', 'processing', 'completed', 'failed') — default 'pending'
- `total_rows` (integer, default 0)
- `imported_rows` (integer, default 0)
- `skipped_rows` (integer, default 0)
- `error_log` (json, nullable)
- `column_mapping` (json, nullable) — stores user's column mapping for CSV imports
- timestamps

Index: `['team_id', 'status']`

Import model:
- `use BelongsToTeam, HasFactory`
- `$fillable`: all columns
- `$casts`: `total_rows` => int, `imported_rows` => int, `skipped_rows` => int, `error_log` => 'array', `column_mapping` => 'array', `file_type` => ImportFileType::class, `status` => ImportStatus::class

### 3. Create Enums
```
app/Enums/ImportFileType.php — backed string enum: 'csv', 'ofx'
app/Enums/ImportStatus.php — backed string enum: 'pending', 'processing', 'completed', 'failed'
```

### 4. Create ImportFactory
Fill with realistic data.

### 5. Create CsvImportParser Service
`app/Services/CsvImportParser.php`

```php
namespace App\Services;

use League\Csv\Reader;

class CsvImportParser
{
    public function parse(string $filePath): array
    {
        $csv = Reader::createFromPath($filePath);
        $csv->setHeaderOffset(0);
        
        $headers = $csv->getHeader();
        $rows = [];
        
        foreach ($csv->getRecords() as $offset => $record) {
            $rows[] = $record;
            if (count($rows) >= 50) break; // preview: first 50 rows
        }
        
        return [
            'headers' => $headers,
            'rows' => $rows,
            'total_rows' => count($csv),
        ];
    }
}
```

### 6. Create OfxImportParser Service
`app/Services/OfxImportParser.php`

Hand-rolled OFX parser. OFX files are SGML-like (not strictly valid XML). Detect OFX version from header:
- OFX 1.x: SGML format (tags have no closing tags in some cases)
- OFX 2.x: XML format

Parse approach:
1. Read the file content
2. Extract `<STMTTRN>...</STMTTRN>` blocks using regex
3. For each block, extract: `<TRNTYPE>`, `<DTPOSTED>`, `<TRNAMT>`, `<NAME>`, `<MEMO>`
4. Also extract account info from `<ACCTID>`, `<BANKID>`, `<ACCTTYPE>`

Return normalized array:
```php
[
    'account_info' => ['account_id' => '...', 'bank_id' => '...', 'account_type' => '...'],
    'transactions' => [
        ['type' => 'DEBIT', 'date' => '2024-01-15', 'amount' => -50.00, 'description' => 'Grocery Store', 'memo' => '...'],
        ...
    ],
    'total_rows' => N,
]
```

OFX date format: YYYYMMDD → convert to YYYY-MM-DD.
OFX amount format: string with optional sign. DEBIT = negative, CREDIT = positive.

### 7. Create ImportDeduplicationService
`app/Services/ImportDeduplicationService.php`

```php
namespace App\Services;

use App\Models\Transaction;

class ImportDeduplicationService
{
    public function isDuplicate(int $accountId, string $date, float $amount, string $description): bool
    {
        // Check for existing transaction: same account, same date, amount within tolerance, similar description
        $tolerance = 0.01; // 1 cent tolerance
        
        $existing = Transaction::query()
            ->where('account_id', $accountId)
            ->where('transaction_date', $date)
            ->whereBetween('amount', [$amount - $tolerance, $amount + $tolerance])
            ->get();
        
        foreach ($existing as $txn) {
            if ($txn->description && $description) {
                similar_text(
                    strtolower(trim($txn->description)),
                    strtolower(trim($description)),
                    $percent
                );
                if ($percent > 75) {
                    return true;
                }
            }
        }
        
        return false;
    }
}
```

### 8. Create ImportController
`app/Http/Controllers/Api/ImportController.php`

Methods:
- `index()` — list imports for current team, paginated
- `upload(Request $request)` — accept file upload (csv/ofx), store to `storage/app/imports/`, create Import row, for CSV return headers + parsed preview, for OFX return parsed transactions
- `preview(Import $import)` — return parsed preview data (re-parse if needed)
- `process(Import $import)` — dispatch ProcessCsvImport or ProcessOfxImport job based on file_type
- `confirm(Request $request, Import $import)` — accept column mapping (for CSV) + selected rows to import, then dispatch the import job
- `destroy(Import $import)` — delete import record and file

Important: The `confirm` method should accept `column_mapping` (for CSV) and optionally `selected_rows` (indices to import). After confirming, dispatch the appropriate job.

### 9. Create ProcessCsvImport Job
`app/Jobs/ProcessCsvImport.php`

```php
- Read the CSV file
- Apply column_mapping from the Import model to map CSV columns to transaction fields
- For each row:
  - Check deduplication via ImportDeduplicationService
  - If duplicate: increment skipped_rows
  - If not duplicate: call CreateTransactionAction with source='import'
- Update Import status to 'completed' (or 'failed' on exception)
- After completion, dispatch AutoCategorizeImport
```

### 10. Create ProcessOfxImport Job
`app/Jobs/ProcessOfxImport.php`

Same pattern as CSV but uses OfxImportParser, no column mapping needed.

### 11. Create AutoCategorizeImport Job
`app/Jobs/AutoCategorizeImport.php`

For each imported transaction without a category:
- Use simple keyword matching (similar to TransactionSuggestionController): find existing transactions with similar description that have categories
- Assign the most common category
- If no match found, leave uncategorized

### 12. Register Import Routes
In `routes/api.php` under `auth:sanctum`:
```php
Route::prefix('imports')->group(function () {
    Route::get('/', [ImportController::class, 'index']);
    Route::post('/upload', [ImportController::class, 'upload']);
    Route::get('/{import}/preview', [ImportController::class, 'preview']);
    Route::post('/{import}/process', [ImportController::class, 'process']);
    Route::post('/{import}/confirm', [ImportController::class, 'confirm']);
    Route::delete('/{import}', [ImportController::class, 'destroy']);
});
```

### 13. Create Test Fixtures
- `tests/Fixtures/sample.csv` — a simple CSV with 5-10 transactions (headers: Date, Description, Amount, Category)
- `tests/Fixtures/sample.ofx` — a valid OFX 1.x file with 3-5 STMTTRN blocks

### 14. Write Tests
**tests/Unit/Services/CsvImportParserTest.php:**
- Test parsing CSV returns headers and rows
- Test first 50 rows limit

**tests/Unit/Services/OfxImportParserTest.php:**
- Test parsing OFX file returns transactions
- Test date format conversion
- Test amount parsing

**tests/Feature/ImportTest.php:**
- Test upload CSV file
- Test upload OFX file
- Test preview endpoint
- Test confirm endpoint
- Test deduplication
- Test import process completes
- Test team isolation
- Test validation (invalid file types)

### 15. Verify
After all implementation, run:
```bash
php artisan test
```
All tests must pass (66 existing + new ones).

## TDD Workflow
For each test file, write the test FIRST, verify it FAILS, then implement, verify it PASSES.