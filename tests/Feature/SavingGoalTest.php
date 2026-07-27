<?php

namespace Tests\Feature;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Models\Account;
use App\Models\SavingGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingGoalTest extends TestCase
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
            'balance' => 10000.00,
        ]);

        $this->actingAs($this->user);
    }

    public function test_user_can_list_saving_goals(): void
    {
        SavingGoal::factory()->count(3)->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/saving-goals');

        $response->assertOk()
            ->assertJsonStructure(['data', 'message', 'errors', 'meta'])
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_saving_goal(): void
    {
        $data = [
            'name' => 'Emergency Fund',
            'target_amount' => 10000.00,
            'currency' => 'USD',
            'deadline' => '2026-12-31',
            'color' => '#FF6B6B',
            'icon' => 'shield',
        ];

        $response = $this->postJson('/api/saving-goals', $data);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Emergency Fund')
            ->assertJsonPath('data.target_amount', '10000.00');

        $this->assertDatabaseHas('saving_goals', [
            'name' => 'Emergency Fund',
            'team_id' => $this->user->current_team_id,
        ]);
    }

    public function test_user_can_view_saving_goal(): void
    {
        $goal = SavingGoal::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/saving-goals/{$goal->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $goal->id);
    }

    public function test_user_can_update_saving_goal(): void
    {
        $goal = SavingGoal::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'name' => 'Old Name',
        ]);

        $response = $this->putJson("/api/saving-goals/{$goal->id}", [
            'name' => 'New Name',
            'target_amount' => $goal->target_amount,
            'currency' => $goal->currency,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_user_can_delete_saving_goal(): void
    {
        $goal = SavingGoal::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/saving-goals/{$goal->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('saving_goals', ['id' => $goal->id]);
    }

    public function test_user_can_add_contribution_to_goal(): void
    {
        $goal = SavingGoal::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'target_amount' => 1000.00,
            'current_amount' => 0.00,
            'is_completed' => false,
        ]);

        $response = $this->postJson("/api/saving-goals/{$goal->id}/contributions", [
            'amount' => 250.00,
            'contributed_at' => '2026-07-27',
            'note' => 'July contribution',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.goal.current_amount', '250.00')
            ->assertJsonPath('data.goal.is_completed', false);

        $this->assertDatabaseHas('goal_contributions', [
            'saving_goal_id' => $goal->id,
            'amount' => 250.00,
        ]);
    }

    public function test_goal_is_marked_completed_when_target_reached(): void
    {
        $goal = SavingGoal::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'target_amount' => 1000.00,
            'current_amount' => 800.00,
            'is_completed' => false,
            'completed_at' => null,
        ]);

        $response = $this->postJson("/api/saving-goals/{$goal->id}/contributions", [
            'amount' => 250.00,
            'contributed_at' => '2026-07-27',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.goal.current_amount', '1050.00')
            ->assertJsonPath('data.goal.is_completed', true);
    }

    public function test_saving_goals_are_team_isolated(): void
    {
        $otherUser = User::factory()->create();
        (new CreatePersonalTeamAction)->execute($otherUser);
        $otherUser->refresh();

        SavingGoal::factory()->create([
            'team_id' => $otherUser->current_team_id,
            'user_id' => $otherUser->id,
            'name' => 'Other Team Goal',
        ]);

        SavingGoal::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'name' => 'My Goal',
        ]);

        $response = $this->getJson('/api/saving-goals');

        $response->assertOk();
        $data = $response->json('data');
        $names = array_column($data, 'name');
        $this->assertContains('My Goal', $names);
        $this->assertNotContains('Other Team Goal', $names);
    }
}