<?php

namespace Tests\Feature;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Enums\VoiceJobStatus;
use App\Models\Account;
use App\Models\User;
use App\Models\VoiceJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VoiceTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Http::fake();
        Storage::fake('voice_notes');

        $this->user = User::factory()->create();
        (new CreatePersonalTeamAction)->execute($this->user);

        $this->account = Account::factory()->create([
            'team_id' => $this->user->current_team_id,
            'currency' => 'IDR',
            'balance' => 5000000.00,
        ]);

        $this->actingAs($this->user);
    }

    public function test_user_can_upload_voice_and_get_job_id(): void
    {
        Queue::fake();

        $file = UploadedFile::fake()->create('recording.mp3', 100, 'audio/mpeg');

        $response = $this->postJson('/api/transactions/voice', [
            'audio' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.id', fn ($id) => is_int($id))
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('message', 'Voice note uploaded, processing started');

        $this->assertDatabaseHas('voice_jobs', [
            'user_id' => $this->user->id,
            'team_id' => $this->user->current_team_id,
            'status' => VoiceJobStatus::Pending->value,
        ]);
    }

    public function test_user_can_poll_voice_status_and_get_result(): void
    {
        $voiceJob = VoiceJob::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'status' => VoiceJobStatus::Completed,
            'transcript' => 'Makan siang 50 ribu hari ini',
            'result' => [
                'amount' => 50000,
                'merchant' => 'Makan siang',
                'type' => 'expense',
                'category' => null,
                'notes' => 'Makan siang 50 ribu hari ini',
            ],
        ]);

        $response = $this->getJson("/api/transactions/voice/{$voiceJob->id}/status");

        $response->assertOk()
            ->assertJsonPath('data.id', $voiceJob->id)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.result.amount', 50000)
            ->assertJsonPath('data.result.type', 'expense');
    }

    public function test_voice_job_belongs_to_team(): void
    {
        $voiceJob = VoiceJob::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
        ]);

        $userB = User::factory()->create();
        (new CreatePersonalTeamAction)->execute($userB);
        $this->actingAs($userB);

        $response = $this->getJson("/api/transactions/voice/{$voiceJob->id}/status");

        $response->assertStatus(404);
    }
}
