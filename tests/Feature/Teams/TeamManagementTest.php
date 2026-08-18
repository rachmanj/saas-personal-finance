<?php

namespace Tests\Feature\Teams;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamManagementTest extends TestCase
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

    protected function createBusinessTeam(User $owner): Team
    {
        $team = $owner->ownedTeams()->create([
            'name' => 'My Business',
            'personal_team' => false,
        ]);
        $team->users()->attach($owner, ['role' => 'owner']);

        return $team;
    }

    public function test_registration_creates_personal_team_and_sets_current_team_id(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/dashboard');

        $user = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($user->current_team_id);
        $this->assertDatabaseHas('teams', [
            'id' => $user->current_team_id,
            'user_id' => $user->id,
            'personal_team' => true,
        ]);
        $this->assertDatabaseHas('team_user', [
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
    }

    public function test_user_can_view_teams_page(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        $response = $this->get('/teams');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Teams/Index')
                ->has('teams', 1)
                ->where('current_team_id', $user->current_team_id));
    }

    public function test_user_can_create_team(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        $response = $this->post('/teams', [
            'name' => 'Acme Corp',
        ]);

        $response->assertRedirect('/teams');

        $this->assertDatabaseHas('teams', [
            'name' => 'Acme Corp',
            'user_id' => $user->id,
            'personal_team' => false,
        ]);

        $team = Team::where('name', 'Acme Corp')->first();
        $this->assertDatabaseHas('team_user', [
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        $this->assertEquals($team->id, $user->fresh()->current_team_id);
    }

    public function test_user_can_switch_team(): void
    {
        $user = $this->createUserWithTeam();
        $personalTeamId = $user->current_team_id;
        $team = $this->createBusinessTeam($user);

        $this->actingAs($user);

        $response = $this->post("/teams/{$team->id}/switch");

        $response->assertRedirect('/dashboard');
        $this->assertEquals($team->id, $user->fresh()->current_team_id);
        $this->assertNotEquals($personalTeamId, $user->fresh()->current_team_id);
    }

    public function test_owner_can_invite_member_by_email(): void
    {
        $owner = $this->createUserWithTeam();
        $team = $this->createBusinessTeam($owner);

        $this->actingAs($owner);

        $response = $this->post("/teams/{$team->id}/invite", [
            'email' => 'member@example.com',
            'role' => 'member',
        ]);

        $response->assertRedirect('/teams');

        $this->assertDatabaseHas('team_invitations', [
            'team_id' => $team->id,
            'email' => 'member@example.com',
            'role' => 'member',
        ]);
    }

    public function test_invite_existing_user_auto_attaches_them(): void
    {
        $owner = $this->createUserWithTeam();
        $team = $this->createBusinessTeam($owner);
        $member = User::factory()->create();

        $this->actingAs($owner);

        $response = $this->post("/teams/{$team->id}/invite", [
            'email' => $member->email,
        ]);

        $response->assertRedirect('/teams');

        $this->assertDatabaseHas('team_user', [
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);
    }

    public function test_owner_can_remove_member(): void
    {
        $owner = $this->createUserWithTeam();
        $team = $this->createBusinessTeam($owner);
        $member = User::factory()->create();
        $team->users()->attach($member, ['role' => 'member']);

        $this->actingAs($owner);

        $response = $this->delete("/teams/{$team->id}/members/{$member->id}");

        $response->assertRedirect('/teams');
        $this->assertDatabaseMissing('team_user', [
            'team_id' => $team->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_non_member_cannot_switch_to_team(): void
    {
        $owner = $this->createUserWithTeam();
        $team = $this->createBusinessTeam($owner);

        $intruder = $this->createUserWithTeam();
        $intruderTeamId = $intruder->current_team_id;

        $this->actingAs($intruder);

        $response = $this->post("/teams/{$team->id}/switch");

        $response->assertForbidden();
        $this->assertEquals($intruderTeamId, $intruder->fresh()->current_team_id);
    }

    public function test_owner_cannot_remove_self(): void
    {
        $owner = $this->createUserWithTeam();
        $team = $this->createBusinessTeam($owner);

        $this->actingAs($owner);

        $response = $this->delete("/teams/{$team->id}/members/{$owner->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('team_user', [
            'team_id' => $team->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_owner_cannot_delete_personal_team(): void
    {
        $owner = $this->createUserWithTeam();
        $personalTeamId = $owner->current_team_id;

        $this->actingAs($owner);

        $response = $this->delete("/teams/{$personalTeamId}");

        $response->assertForbidden();
        $this->assertDatabaseHas('teams', ['id' => $personalTeamId]);
    }

    public function test_owner_can_delete_business_team(): void
    {
        $owner = $this->createUserWithTeam();
        $team = $this->createBusinessTeam($owner);

        $this->actingAs($owner);

        $response = $this->delete("/teams/{$team->id}");

        $response->assertRedirect('/teams');
        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
    }
}
