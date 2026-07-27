<?php

namespace Tests\Feature\Auth;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_user_without_team_cannot_access_protected_route(): void
    {
        $user = User::factory()->create([
            'current_team_id' => null,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertForbidden();
    }

    public function test_user_with_team_can_access_protected_route(): void
    {
        $user = User::factory()->create();
        (new CreatePersonalTeamAction)->execute($user);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
    }
}
