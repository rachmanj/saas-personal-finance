<?php

namespace Tests\Feature;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
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

    public function test_user_can_list_categories(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/categories');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_category(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        $data = [
            'name' => 'Groceries',
            'type' => 'expense',
            'color' => '#FF0000',
        ];

        $response = $this->postJson('/api/categories', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Groceries');

        $this->assertDatabaseHas('categories', [
            'name' => 'Groceries',
            'type' => 'expense',
            'team_id' => $user->current_team_id,
        ]);
    }

    public function test_user_can_view_category(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        $category = Category::factory()->create();

        $response = $this->getJson("/api/categories/{$category->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.name', $category->name);
    }

    public function test_user_can_update_category(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        $category = Category::factory()->create();

        $response = $this->putJson("/api/categories/{$category->id}", [
            'name' => 'Updated Category',
            'type' => 'income',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Category');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Category',
            'type' => 'income',
        ]);
    }

    public function test_user_can_delete_category(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_category_validation(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        $response = $this->postJson('/api/categories', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type']);
    }

    public function test_category_parent_child_relationship(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        $parent = Category::factory()->create(['type' => 'expense']);

        $child = Category::factory()->create([
            'type' => 'expense',
            'parent_id' => $parent->id,
        ]);

        $response = $this->getJson("/api/categories/{$child->id}");

        $response->assertOk()
            ->assertJsonPath('data.parent.id', $parent->id);
    }

    public function test_category_team_isolation(): void
    {
        $userA = $this->createUserWithTeam();
        $userB = $this->createUserWithTeam();

        $this->actingAs($userA);
        Category::factory()->create(['name' => 'Team A Category']);

        $this->actingAs($userB);
        $response = $this->getJson('/api/categories');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_can_reorder_categories(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        $cat1 = Category::factory()->create(['sort_order' => 1]);
        $cat2 = Category::factory()->create(['sort_order' => 2]);
        $cat3 = Category::factory()->create(['sort_order' => 3]);

        $response = $this->putJson('/api/categories/reorder', [
            'ordered_ids' => [$cat3->id, $cat1->id, $cat2->id],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('categories', ['id' => $cat3->id, 'sort_order' => 1]);
        $this->assertDatabaseHas('categories', ['id' => $cat1->id, 'sort_order' => 2]);
        $this->assertDatabaseHas('categories', ['id' => $cat2->id, 'sort_order' => 3]);
    }
}
