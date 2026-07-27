<?php

namespace Tests\Feature;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Actions\Transactions\CreateTransactionAction;
use App\Enums\ImportFileType;
use App\Enums\ImportStatus;
use App\Jobs\AutoCategorizeImport;
use App\Jobs\ProcessCsvImport;
use App\Jobs\ProcessOfxImport;
use App\Models\Account;
use App\Models\Category;
use App\Models\Import;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CurrencyConverterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Storage::fake('imports');

        $this->user = User::factory()->create();
        (new CreatePersonalTeamAction)->execute($this->user);

        $this->account = Account::factory()->create([
            'team_id' => $this->user->current_team_id,
            'currency' => 'USD',
            'balance' => 5000.00,
        ]);

        $this->actingAs($this->user);
    }

    public function test_user_can_upload_csv_file(): void
    {
        $file = new UploadedFile(
            base_path('tests/Fixtures/sample.csv'),
            'sample.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->postJson('/api/imports/upload', [
            'file' => $file,
            'account_id' => $this->account->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.file_type', 'csv')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.preview.headers', ['Date', 'Description', 'Amount', 'Category'])
            ->assertJsonPath('message', 'File uploaded successfully');

        $this->assertDatabaseHas('imports', [
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'file_type' => ImportFileType::Csv->value,
            'status' => ImportStatus::Pending->value,
        ]);
    }

    public function test_user_can_upload_ofx_file(): void
    {
        $file = new UploadedFile(
            base_path('tests/Fixtures/sample.ofx'),
            'sample.ofx',
            'application/x-ofx',
            null,
            true
        );

        $response = $this->postJson('/api/imports/upload', [
            'file' => $file,
            'account_id' => $this->account->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.file_type', 'ofx')
            ->assertJsonPath('data.preview.account_info.account_id', '9876543210')
            ->assertJsonCount(4, 'data.preview.transactions');

        $this->assertDatabaseHas('imports', [
            'file_type' => ImportFileType::Ofx->value,
        ]);
    }

    public function test_user_can_preview_import(): void
    {
        $import = $this->createImportFromFixture('sample.csv', ImportFileType::Csv);

        $response = $this->getJson("/api/imports/{$import->id}/preview");

        $response->assertOk()
            ->assertJsonPath('data.headers', ['Date', 'Description', 'Amount', 'Category'])
            ->assertJsonCount(8, 'data.rows');
    }

    public function test_user_can_confirm_csv_import(): void
    {
        Queue::fake();

        $import = $this->createImportFromFixture('sample.csv', ImportFileType::Csv);

        $columnMapping = [
            'date' => 'Date',
            'description' => 'Description',
            'amount' => 'Amount',
            'selected_rows' => [0, 1, 2],
        ];

        $response = $this->postJson("/api/imports/{$import->id}/confirm", [
            'column_mapping' => $columnMapping,
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Import confirmed, processing started');

        $import->refresh();
        $this->assertEquals($columnMapping, $import->column_mapping);

        Queue::assertPushed(ProcessCsvImport::class, function ($job) use ($import) {
            return $job->import->id === $import->id;
        });
    }

    public function test_deduplication_skips_duplicate_transactions(): void
    {
        $action = new CreateTransactionAction(new CurrencyConverterService);

        $action->execute([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'type' => 'expense',
            'amount' => 50.00,
            'currency' => 'USD',
            'description' => 'Grocery Store',
            'transaction_date' => '2024-01-15',
            'source' => 'manual',
        ]);

        $import = $this->createImportFromFixture('sample.csv', ImportFileType::Csv);
        $import->update([
            'column_mapping' => [
                'date' => 'Date',
                'description' => 'Description',
                'amount' => 'Amount',
                'selected_rows' => [0],
            ],
        ]);

        $job = new ProcessCsvImport($import);
        $job->handle(
            app(\App\Services\CsvImportParser::class),
            app(\App\Services\ImportDeduplicationService::class),
            app(CreateTransactionAction::class)
        );

        $import->refresh();

        $this->assertEquals(ImportStatus::Completed, $import->status);
        $this->assertEquals(0, $import->imported_rows);
        $this->assertEquals(1, $import->skipped_rows);
        $this->assertEquals(0, Transaction::where('source', 'import')->count());
    }

    public function test_import_process_completes(): void
    {
        $import = $this->createImportFromFixture('sample.csv', ImportFileType::Csv);
        $import->update([
            'column_mapping' => [
                'date' => 'Date',
                'description' => 'Description',
                'amount' => 'Amount',
                'selected_rows' => [0, 1],
            ],
        ]);

        $job = new ProcessCsvImport($import);
        $job->handle(
            app(\App\Services\CsvImportParser::class),
            app(\App\Services\ImportDeduplicationService::class),
            app(CreateTransactionAction::class)
        );

        $import->refresh();

        $this->assertEquals(ImportStatus::Completed, $import->status);
        $this->assertEquals(2, $import->imported_rows);
        $this->assertEquals(0, $import->skipped_rows);
        $this->assertEquals(2, Transaction::where('source', 'import')->count());
    }

    public function test_ofx_import_process_completes(): void
    {
        $import = $this->createImportFromFixture('sample.ofx', ImportFileType::Ofx);

        $job = new ProcessOfxImport($import);
        $job->handle(
            app(\App\Services\OfxImportParser::class),
            app(\App\Services\ImportDeduplicationService::class),
            app(CreateTransactionAction::class)
        );

        $import->refresh();

        $this->assertEquals(ImportStatus::Completed, $import->status);
        $this->assertEquals(4, $import->imported_rows);
        $this->assertEquals(4, Transaction::where('source', 'import')->count());
    }

    public function test_user_can_process_ofx_import_via_endpoint(): void
    {
        Queue::fake();

        $import = $this->createImportFromFixture('sample.ofx', ImportFileType::Ofx);

        $response = $this->postJson("/api/imports/{$import->id}/process");

        $response->assertOk()
            ->assertJsonPath('message', 'Import processing started');

        Queue::assertPushed(ProcessOfxImport::class);
    }

    public function test_import_team_isolation(): void
    {
        $import = $this->createImportFromFixture('sample.csv', ImportFileType::Csv);

        $userB = User::factory()->create();
        (new CreatePersonalTeamAction)->execute($userB);
        $this->actingAs($userB);

        $response = $this->getJson("/api/imports/{$import->id}/preview");

        $response->assertStatus(404);
    }

    public function test_upload_rejects_invalid_file_type(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->postJson('/api/imports/upload', [
            'file' => $file,
            'account_id' => $this->account->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_user_can_list_imports(): void
    {
        Import::factory()->count(2)->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $response = $this->getJson('/api/imports');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_delete_import(): void
    {
        $import = $this->createImportFromFixture('sample.csv', ImportFileType::Csv);

        $response = $this->deleteJson("/api/imports/{$import->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('imports', ['id' => $import->id]);
        Storage::disk('imports')->assertMissing($import->file_path);
    }

    public function test_auto_categorize_assigns_category_from_similar_transactions(): void
    {
        Queue::fake();

        $category = Category::factory()->create([
            'team_id' => $this->user->current_team_id,
            'name' => 'Groceries',
        ]);

        $action = new CreateTransactionAction(new CurrencyConverterService);
        $action->execute([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 30.00,
            'currency' => 'USD',
            'description' => 'Grocery Store Purchase',
            'transaction_date' => '2024-01-10',
            'source' => 'manual',
        ]);

        $import = $this->createImportFromFixture('sample.csv', ImportFileType::Csv);
        $import->update([
            'column_mapping' => [
                'date' => 'Date',
                'description' => 'Description',
                'amount' => 'Amount',
                'selected_rows' => [0],
            ],
        ]);

        $processJob = new ProcessCsvImport($import);
        $processJob->handle(
            app(\App\Services\CsvImportParser::class),
            app(\App\Services\ImportDeduplicationService::class),
            app(CreateTransactionAction::class)
        );

        $importedTransaction = Transaction::where('source', 'import')->first();
        $this->assertNull($importedTransaction->category_id);

        $autoJob = new AutoCategorizeImport($import, [$importedTransaction->id]);
        $autoJob->handle();

        $importedTransaction->refresh();
        $this->assertEquals($category->id, $importedTransaction->category_id);
    }

    private function createImportFromFixture(string $filename, ImportFileType $fileType): Import
    {
        $sourcePath = base_path("tests/Fixtures/{$filename}");
        $storedPath = "imports/{$this->user->current_team_id}/{$filename}";
        Storage::disk('imports')->put($storedPath, file_get_contents($sourcePath));

        return Import::create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'file_path' => $storedPath,
            'file_type' => $fileType,
            'status' => ImportStatus::Pending,
        ]);
    }
}
