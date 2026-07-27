<?php

namespace Tests\Feature;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Enums\OcrJobStatus;
use App\Models\Account;
use App\Models\Category;
use App\Models\OcrJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OcrTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Account $account;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Storage::fake('receipts');

        $this->user = User::factory()->create();
        (new CreatePersonalTeamAction)->execute($this->user);

        $this->account = Account::factory()->create([
            'team_id' => $this->user->current_team_id,
            'currency' => 'USD',
            'balance' => 5000.00,
        ]);

        $this->category = Category::factory()->create([
            'team_id' => $this->user->current_team_id,
        ]);

        $this->actingAs($this->user);
    }

    public function test_user_can_upload_receipt_and_get_job_id(): void
    {
        Queue::fake();

        $file = UploadedFile::fake()->image('receipt.jpg', 800, 600);

        $response = $this->postJson('/api/transactions/ocr', [
            'receipt' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.id', fn ($id) => is_int($id))
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('message', 'Receipt uploaded, processing started');

        $this->assertDatabaseHas('ocr_jobs', [
            'user_id' => $this->user->id,
            'team_id' => $this->user->current_team_id,
            'status' => OcrJobStatus::Pending->value,
        ]);
    }

    public function test_user_can_poll_ocr_status_and_get_result(): void
    {
        $ocrJob = OcrJob::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'status' => OcrJobStatus::Completed,
            'result' => [
                'merchant' => 'Starbucks',
                'amount' => 55000,
                'date' => now()->toDateString(),
                'raw_text' => 'Starbucks receipt',
            ],
        ]);

        $response = $this->getJson("/api/transactions/ocr/{$ocrJob->id}/status");

        $response->assertOk()
            ->assertJsonPath('data.id', $ocrJob->id)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.result.merchant', 'Starbucks')
            ->assertJsonPath('data.result.amount', 55000);
    }

    public function test_ocr_job_belongs_to_team(): void
    {
        $ocrJob = OcrJob::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
        ]);

        $userB = User::factory()->create();
        (new CreatePersonalTeamAction)->execute($userB);
        $this->actingAs($userB);

        $response = $this->getJson("/api/transactions/ocr/{$ocrJob->id}/status");

        $response->assertStatus(404);
    }
}
