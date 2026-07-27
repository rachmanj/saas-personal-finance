<?php

namespace Tests\Feature;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function createUserWithTeam(): User
    {
        $user = User::factory()->create();
        (new CreatePersonalTeamAction)->execute($user);

        return $user;
    }

    public function test_user_can_list_their_accounts(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        Account::factory()->count(3)->create();

        $response = $this->getJson('/api/accounts');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_account(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        $data = [
            'name' => 'My Checking',
            'type' => 'checking',
            'currency' => 'USD',
            'initial_balance' => 1000.00,
            'include_in_net_worth' => true,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/accounts', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'My Checking');

        $this->assertDatabaseHas('accounts', [
            'name' => 'My Checking',
            'type' => 'checking',
            'currency' => 'USD',
            'team_id' => $user->current_team_id,
        ]);
    }

    public function test_user_can_view_account(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        $account = Account::factory()->create();

        $response = $this->getJson("/api/accounts/{$account->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $account->id)
            ->assertJsonPath('data.name', $account->name);
    }

    public function test_user_can_update_account(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        $account = Account::factory()->create();

        $response = $this->putJson("/api/accounts/{$account->id}", [
            'name' => 'Updated Account',
            'type' => 'savings',
            'currency' => 'EUR',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Account');

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => 'Updated Account',
            'type' => 'savings',
            'currency' => 'EUR',
        ]);
    }

    public function test_user_can_delete_account(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        $account = Account::factory()->create();

        $response = $this->deleteJson("/api/accounts/{$account->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('accounts', [
            'id' => $account->id,
        ]);
    }

    public function test_account_validation_required_fields(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        $response = $this->postJson('/api/accounts', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type', 'currency']);
    }

    public function test_account_team_isolation(): void
    {
        $userA = $this->createUserWithTeam();
        $userB = $this->createUserWithTeam();

        $this->actingAs($userA);
        $accountA = Account::factory()->create(['name' => 'Team A Account']);

        $this->actingAs($userB);
        $response = $this->getJson('/api/accounts');

        $response->assertOk()
            ->assertJsonCount(0, 'data');

        $names = collect($response->json('data'))->pluck('name');
        $this->assertFalse($names->contains('Team A Account'));
    }
}
