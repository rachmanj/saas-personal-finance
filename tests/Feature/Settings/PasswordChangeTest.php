<?php

namespace Tests\Feature\Settings;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithTeam(string $password = 'password123'): User
    {
        $user = User::factory()->create([
            'password' => Hash::make($password),
        ]);

        (new CreatePersonalTeamAction)->execute($user);

        return $user;
    }

    public function test_user_can_change_own_password(): void
    {
        $user = $this->createUserWithTeam();

        $this->actingAs($user);

        $response = $this->from('/settings/password')->put('/settings/password', [
            'current_password' => 'password123',
            'password' => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ]);

        $response->assertRedirect();

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword456', $user->password));
    }

    public function test_password_change_requires_correct_current_password(): void
    {
        $user = $this->createUserWithTeam();

        $this->actingAs($user);

        $response = $this->from('/settings/password')->put('/settings/password', [
            'current_password' => 'wrong-password',
            'password' => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ]);

        $response->assertSessionHasErrors('current_password');

        $user->refresh();
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_password_change_requires_confirmation(): void
    {
        $user = $this->createUserWithTeam();

        $this->actingAs($user);

        $response = $this->from('/settings/password')->put('/settings/password', [
            'current_password' => 'password123',
            'password' => 'newpassword456',
            'password_confirmation' => 'different123',
        ]);

        $response->assertSessionHasErrors('password');

        $user->refresh();
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_password_must_be_at_least_8_chars(): void
    {
        $user = $this->createUserWithTeam();

        $this->actingAs($user);

        $response = $this->from('/settings/password')->put('/settings/password', [
            'current_password' => 'password123',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
    }
}
