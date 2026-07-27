<?php

namespace Tests\Feature;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
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

    public function test_user_can_list_tags(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        Tag::factory()->count(3)->create();

        $response = $this->getJson('/api/tags');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_tag(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        $data = [
            'name' => 'Vacation',
            'color' => '#00FF00',
        ];

        $response = $this->postJson('/api/tags', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Vacation');

        $this->assertDatabaseHas('tags', [
            'name' => 'Vacation',
            'team_id' => $user->current_team_id,
        ]);
    }

    public function test_user_can_view_tag(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        $tag = Tag::factory()->create();

        $response = $this->getJson("/api/tags/{$tag->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $tag->id)
            ->assertJsonPath('data.name', $tag->name);
    }

    public function test_user_can_update_tag(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        $tag = Tag::factory()->create();

        $response = $this->putJson("/api/tags/{$tag->id}", [
            'name' => 'Updated Tag',
            'color' => '#0000FF',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Tag');

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'Updated Tag',
        ]);
    }

    public function test_user_can_delete_tag(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        $tag = Tag::factory()->create();

        $response = $this->deleteJson("/api/tags/{$tag->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
        ]);
    }

    public function test_tag_validation(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        $response = $this->postJson('/api/tags', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_tag_name_unique_per_team(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        Tag::factory()->create(['name' => 'Duplicate']);

        $response = $this->postJson('/api/tags', [
            'name' => 'Duplicate',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_tag_team_isolation(): void
    {
        $userA = $this->createUserWithTeam();
        $userB = $this->createUserWithTeam();

        $this->actingAs($userA);
        Tag::factory()->create(['name' => 'Team A Tag']);

        $this->actingAs($userB);
        $response = $this->getJson('/api/tags');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
