<?php

namespace Tests\Feature\Auth;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_personal_team(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        $user = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($user->current_team_id);

        $this->assertDatabaseHas('teams', [
            'id' => $user->current_team_id,
            'user_id' => $user->id,
            'personal_team' => true,
        ]);

        $team = Team::find($user->current_team_id);
        $this->assertSame("Test User's Team", $team->name);

        $this->assertDatabaseHas('team_user', [
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
    }
}
