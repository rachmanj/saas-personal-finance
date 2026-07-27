<?php

namespace Tests\Feature;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Models\Account;
use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\RecurringTransactionLog;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Account $account;

    protected Category $category;

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

        $this->category = Category::factory()->create([
            'team_id' => $this->user->current_team_id,
            'type' => 'expense',
        ]);

        $this->actingAs($this->user);
    }

    public function test_user_can_list_recurring_transactions(): void
    {
        RecurringTransaction::factory()->count(3)->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $response = $this->getJson('/api/recurring-transactions');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_recurring_transaction(): void
    {
        $data = [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 99.99,
            'currency' => 'USD',
            'description' => 'Monthly rent',
            'frequency' => 'monthly',
            'interval' => 1,
            'start_date' => now()->toDateString(),
            'template_type' => 'rent',
        ];

        $response = $this->postJson('/api/recurring-transactions', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.amount', '99.99')
            ->assertJsonPath('data.frequency', 'monthly');

        $this->assertDatabaseHas('recurring_transactions', [
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'amount' => 99.99,
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_can_view_recurring_transaction(): void
    {
        $recurring = RecurringTransaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson("/api/recurring-transactions/{$recurring->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $recurring->id)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'amount',
                    'frequency',
                    'account',
                    'category',
                    'logs',
                ],
            ]);
    }

    public function test_user_can_update_recurring_transaction(): void
    {
        $recurring = RecurringTransaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->putJson("/api/recurring-transactions/{$recurring->id}", [
            'amount' => 150.00,
            'description' => 'Updated subscription',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.amount', '150.00')
            ->assertJsonPath('data.description', 'Updated subscription');

        $this->assertDatabaseHas('recurring_transactions', [
            'id' => $recurring->id,
            'amount' => 150.00,
            'description' => 'Updated subscription',
        ]);
    }

    public function test_user_can_delete_recurring_transaction(): void
    {
        $recurring = RecurringTransaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $response = $this->deleteJson("/api/recurring-transactions/{$recurring->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('recurring_transactions', [
            'id' => $recurring->id,
        ]);
    }

    public function test_recurring_transaction_validation(): void
    {
        $response = $this->postJson('/api/recurring-transactions', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['account_id', 'type', 'amount', 'currency', 'frequency', 'start_date']);
    }

    public function test_recurring_transaction_team_isolation(): void
    {
        $userB = User::factory()->create();
        (new CreatePersonalTeamAction)->execute($userB);

        RecurringTransaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $this->actingAs($userB);
        $response = $this->getJson('/api/recurring-transactions');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_next_due_date_is_calculated(): void
    {
        $startDate = now()->toDateString();
        $expectedNextDue = now()->addMonth()->toDateString();

        $response = $this->postJson('/api/recurring-transactions', [
            'account_id' => $this->account->id,
            'type' => 'expense',
            'amount' => 50.00,
            'currency' => 'USD',
            'frequency' => 'monthly',
            'interval' => 1,
            'start_date' => $startDate,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.next_due_date', $expectedNextDue);
    }

    public function test_skip_recurring_transaction(): void
    {
        $recurring = RecurringTransaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'frequency' => 'monthly',
            'interval' => 1,
            'next_due_date' => now()->toDateString(),
        ]);

        $response = $this->postJson("/api/recurring-transactions/{$recurring->id}/skip");

        $response->assertOk();

        $this->assertDatabaseHas('recurring_transaction_logs', [
            'recurring_transaction_id' => $recurring->id,
            'was_skipped' => true,
        ]);
    }

    public function test_post_now_recurring_transaction(): void
    {
        $recurring = RecurringTransaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 75.00,
            'currency' => 'USD',
            'description' => 'Netflix',
            'frequency' => 'monthly',
            'interval' => 1,
            'next_due_date' => now()->toDateString(),
        ]);

        $response = $this->postJson("/api/recurring-transactions/{$recurring->id}/post-now");

        $response->assertOk();

        $this->assertDatabaseHas('transactions', [
            'account_id' => $this->account->id,
            'amount' => 75.00,
            'description' => 'Netflix',
        ]);

        $this->assertDatabaseHas('recurring_transaction_logs', [
            'recurring_transaction_id' => $recurring->id,
            'was_skipped' => false,
        ]);

        $log = RecurringTransactionLog::where('recurring_transaction_id', $recurring->id)->first();
        $this->assertNotNull($log->transaction_id);
    }

    public function test_upcoming_recurring_transactions(): void
    {
        RecurringTransaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'is_active' => true,
            'next_due_date' => now()->addDays(5)->toDateString(),
        ]);

        RecurringTransaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'is_active' => true,
            'next_due_date' => now()->addDays(20)->toDateString(),
        ]);

        RecurringTransaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'is_active' => true,
            'next_due_date' => now()->addDays(45)->toDateString(),
        ]);

        $response = $this->getJson('/api/recurring-transactions/upcoming');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
