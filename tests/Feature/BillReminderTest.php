<?php

namespace Tests\Feature;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Models\BillReminder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillReminderTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->user = User::factory()->create();
        (new CreatePersonalTeamAction)->execute($this->user);

        $this->actingAs($this->user);
    }

    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Electric Bill',
            'amount' => 125.50,
            'currency' => 'USD',
            'due_date' => now()->addDays(10)->toDateString(),
            'reminder_days_before' => [1, 3, 7],
            'is_recurring' => false,
        ], $overrides);
    }

    public function test_user_can_create_bill_reminder(): void
    {
        $response = $this->postJson('/api/bill-reminders', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Electric Bill')
            ->assertJsonPath('data.amount', '125.50');

        $this->assertDatabaseHas('bill_reminders', [
            'name' => 'Electric Bill',
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_can_list_bill_reminders(): void
    {
        BillReminder::factory()->count(3)->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/bill-reminders');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_view_bill_reminder(): void
    {
        $reminder = BillReminder::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/bill-reminders/{$reminder->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $reminder->id)
            ->assertJsonPath('data.name', $reminder->name);
    }

    public function test_user_can_update_bill_reminder(): void
    {
        $reminder = BillReminder::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->putJson("/api/bill-reminders/{$reminder->id}", [
            'name' => 'Updated Bill',
            'amount' => 200.00,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Bill')
            ->assertJsonPath('data.amount', '200.00');
    }

    public function test_user_can_delete_bill_reminder(): void
    {
        $reminder = BillReminder::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/bill-reminders/{$reminder->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('bill_reminders', [
            'id' => $reminder->id,
        ]);
    }

    public function test_user_can_toggle_bill_reminder_paid(): void
    {
        $reminder = BillReminder::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'is_paid' => false,
        ]);

        $response = $this->putJson("/api/bill-reminders/{$reminder->id}/paid");

        $response->assertOk()
            ->assertJsonPath('data.is_paid', true);

        $this->assertNotNull($response->json('data.paid_at'));
    }

    public function test_due_soon_returns_unpaid_reminders_within_seven_days(): void
    {
        BillReminder::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'due_date' => now()->addDays(3)->toDateString(),
            'is_paid' => false,
        ]);

        BillReminder::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'due_date' => now()->addDays(10)->toDateString(),
            'is_paid' => false,
        ]);

        BillReminder::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'due_date' => now()->addDays(2)->toDateString(),
            'is_paid' => true,
        ]);

        $response = $this->getJson('/api/bill-reminders/due-soon');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_bill_reminder_team_isolation(): void
    {
        $otherUser = User::factory()->create();
        (new CreatePersonalTeamAction)->execute($otherUser);

        BillReminder::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($otherUser);

        $response = $this->getJson('/api/bill-reminders');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
