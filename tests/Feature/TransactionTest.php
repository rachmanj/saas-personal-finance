<?php

namespace Tests\Feature;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Models\Account;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
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
            'balance' => 5000.00,
        ]);

        $this->actingAs($this->user);
    }

    public function test_user_can_list_transactions(): void
    {
        Transaction::factory()->count(3)->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/transactions');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_income_transaction(): void
    {
        $data = [
            'account_id' => $this->account->id,
            'type' => 'income',
            'amount' => 500.00,
            'currency' => 'USD',
            'description' => 'Salary',
            'transaction_date' => now()->toDateString(),
        ];

        $response = $this->postJson('/api/transactions', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.description', 'Salary')
            ->assertJsonPath('data.type', 'income');

        $this->assertDatabaseHas('transactions', [
            'description' => 'Salary',
            'type' => 'income',
            'amount' => 500.00,
            'team_id' => $this->user->current_team_id,
        ]);
    }

    public function test_user_can_create_expense_transaction(): void
    {
        $data = [
            'account_id' => $this->account->id,
            'type' => 'expense',
            'amount' => 25.50,
            'currency' => 'USD',
            'description' => 'Lunch',
            'transaction_date' => now()->toDateString(),
        ];

        $response = $this->postJson('/api/transactions', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'expense')
            ->assertJsonPath('data.amount', '25.50');
    }

    public function test_user_can_create_transfer_transaction(): void
    {
        $toAccount = Account::factory()->create([
            'team_id' => $this->user->current_team_id,
            'balance' => 1000.00,
        ]);

        $data = [
            'account_id' => $this->account->id,
            'to_account_id' => $toAccount->id,
            'type' => 'transfer',
            'amount' => 300.00,
            'currency' => 'USD',
            'description' => 'Transfer to savings',
            'transaction_date' => now()->toDateString(),
        ];

        $response = $this->postJson('/api/transactions', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'transfer')
            ->assertJsonPath('data.to_account_id', $toAccount->id);
    }

    public function test_transfer_requires_to_account_id(): void
    {
        $data = [
            'account_id' => $this->account->id,
            'type' => 'transfer',
            'amount' => 300.00,
            'currency' => 'USD',
            'description' => 'Incomplete transfer',
            'transaction_date' => now()->toDateString(),
        ];

        $response = $this->postJson('/api/transactions', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to_account_id']);
    }

    public function test_non_transfer_rejects_to_account_id(): void
    {
        $toAccount = Account::factory()->create(['team_id' => $this->user->current_team_id]);

        $data = [
            'account_id' => $this->account->id,
            'to_account_id' => $toAccount->id,
            'type' => 'expense',
            'amount' => 100.00,
            'currency' => 'USD',
            'description' => 'Bad expense',
            'transaction_date' => now()->toDateString(),
        ];

        $response = $this->postJson('/api/transactions', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to_account_id']);
    }

    public function test_user_can_view_transaction(): void
    {
        $transaction = Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/transactions/{$transaction->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $transaction->id);
    }

    public function test_user_can_update_transaction(): void
    {
        $transaction = Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'description' => 'Old description',
        ]);

        $response = $this->putJson("/api/transactions/{$transaction->id}", [
            'description' => 'Updated description',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.description', 'Updated description');

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'description' => 'Updated description',
        ]);
    }

    public function test_user_can_delete_transaction(): void
    {
        $transaction = Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('transactions', [
            'id' => $transaction->id,
        ]);
    }

    public function test_transaction_team_isolation(): void
    {
        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
        ]);

        $userB = User::factory()->create();
        (new CreatePersonalTeamAction)->execute($userB);
        $accountB = Account::factory()->create([
            'team_id' => $userB->current_team_id,
            'balance' => 1000.00,
        ]);
        $this->actingAs($userB);

        $response = $this->getJson('/api/transactions');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_cannot_access_other_team_transaction(): void
    {
        $transaction = Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
        ]);

        $userB = User::factory()->create();
        (new CreatePersonalTeamAction)->execute($userB);
        $this->actingAs($userB);

        $response = $this->getJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(404);
    }

    public function test_transaction_validation_required_fields(): void
    {
        $response = $this->postJson('/api/transactions', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['account_id', 'type', 'amount', 'currency', 'transaction_date']);
    }

    public function test_transaction_filter_by_account(): void
    {
        $account2 = Account::factory()->create([
            'team_id' => $this->user->current_team_id,
            'balance' => 2000.00,
        ]);

        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'description' => 'Account 1 txn',
        ]);
        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $account2->id,
            'user_id' => $this->user->id,
            'description' => 'Account 2 txn',
        ]);

        $response = $this->getJson("/api/transactions?account_id={$this->account->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Account 1 txn');
    }

    public function test_transaction_filter_by_type(): void
    {
        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'type' => 'income',
            'description' => 'Income txn',
        ]);
        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'type' => 'expense',
            'description' => 'Expense txn',
        ]);

        $response = $this->getJson('/api/transactions?type=income');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Income txn');
    }

    public function test_transaction_pagination(): void
    {
        Transaction::factory()->count(25)->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/transactions?current=1&pageSize=10');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_transaction_search(): void
    {
        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'description' => 'Netflix subscription',
        ]);
        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'description' => 'Grocery store',
        ]);

        $response = $this->getJson('/api/transactions?search=Netflix');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Netflix subscription');
    }
}
