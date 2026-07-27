<?php

namespace Tests\Feature;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Jobs\GenerateCsvExport;
use App\Jobs\GeneratePdfReport;
use App\Jobs\SyncGoogleSheets;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->user = User::factory()->create();
        (new CreatePersonalTeamAction)->execute($this->user);

        $this->account = Account::factory()->create([
            'team_id' => $this->user->current_team_id,
            'currency' => 'USD',
        ]);

        $category = Category::factory()->create([
            'team_id' => $this->user->current_team_id,
            'type' => 'expense',
        ]);

        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 50.00,
            'currency' => 'USD',
            'transaction_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->user);
    }

    public function test_pdf_export_dispatches_job(): void
    {
        Queue::fake();

        $response = $this->getJson('/api/export/pdf');

        $response->assertOk()
            ->assertJsonPath('message', 'PDF report generation started')
            ->assertJsonStructure(['data' => ['job_id']]);

        Queue::assertPushed(GeneratePdfReport::class);
    }

    public function test_csv_export_dispatches_job(): void
    {
        Queue::fake();

        $response = $this->getJson('/api/export/csv');

        $response->assertOk()
            ->assertJsonPath('message', 'CSV export generation started')
            ->assertJsonStructure(['data' => ['job_id']]);

        Queue::assertPushed(GenerateCsvExport::class);
    }

    public function test_ofx_export_returns_downloadable_file(): void
    {
        $response = $this->get('/api/export/ofx');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/x-ofx');

        $this->assertStringContainsString('OFXHEADER', $response->getContent());
    }

    public function test_google_sheets_export_dispatches_job(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/export/google-sheets', [
            'spreadsheet_id' => 'abc123',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Google Sheets sync started')
            ->assertJsonStructure(['data' => ['job_id']]);

        Queue::assertPushed(SyncGoogleSheets::class);
    }

    public function test_export_endpoints_require_authentication(): void
    {
        auth()->logout();

        $this->getJson('/api/export/pdf')->assertUnauthorized();
        $this->getJson('/api/export/csv')->assertUnauthorized();
        $this->get('/api/export/ofx')->assertUnauthorized();
        $this->postJson('/api/export/google-sheets', ['spreadsheet_id' => 'abc'])->assertUnauthorized();
    }
}
