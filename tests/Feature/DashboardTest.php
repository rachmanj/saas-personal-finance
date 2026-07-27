<?php

namespace Tests\Feature;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    public function test_dashboard_summary_returns_correct_shape(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        $response = $this->getJson('/api/dashboard/summary');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'income_total',
                    'expense_total',
                    'net_worth',
                    'last_10_transactions',
                    'budgets',
                    'upcoming_recurring',
                    'saving_goals',
                ],
                'message',
                'errors',
                'meta',
            ]);

        $this->assertIsArray($response->json('data.last_10_transactions'));
        $this->assertIsArray($response->json('data.budgets'));
        $this->assertIsArray($response->json('data.upcoming_recurring'));
        $this->assertIsArray($response->json('data.saving_goals'));
    }

    public function test_dashboard_summary_aggregates_current_month_income_and_expense(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        $account = Account::factory()->create([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'currency' => 'USD',
        ]);

        $now = Carbon::now();

        // 3 income transactions this month
        Transaction::factory()->create([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'type' => TransactionType::Income->value,
            'amount' => 1000.00,
            'base_amount' => 1000.00,
            'base_currency' => 'USD',
            'currency' => 'USD',
            'transaction_date' => $now->copy()->startOfMonth()->addDay(1),
        ]);
        Transaction::factory()->create([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'type' => TransactionType::Income->value,
            'amount' => 500.00,
            'base_amount' => 500.00,
            'base_currency' => 'USD',
            'currency' => 'USD',
            'transaction_date' => $now->copy()->startOfMonth()->addDay(5),
        ]);
        Transaction::factory()->create([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'type' => TransactionType::Income->value,
            'amount' => 250.00,
            'base_amount' => 250.00,
            'base_currency' => 'USD',
            'currency' => 'USD',
            'transaction_date' => $now->copy()->startOfMonth()->addDay(10),
        ]);

        // 2 expense transactions this month
        Transaction::factory()->create([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'type' => TransactionType::Expense->value,
            'amount' => 300.00,
            'base_amount' => 300.00,
            'base_currency' => 'USD',
            'currency' => 'USD',
            'transaction_date' => $now->copy()->startOfMonth()->addDay(3),
        ]);
        Transaction::factory()->create([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'type' => TransactionType::Expense->value,
            'amount' => 200.00,
            'base_amount' => 200.00,
            'base_currency' => 'USD',
            'currency' => 'USD',
            'transaction_date' => $now->copy()->startOfMonth()->addDay(8),
        ]);

        // 1 income from last month (should be excluded)
        Transaction::factory()->create([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'type' => TransactionType::Income->value,
            'amount' => 9999.00,
            'base_amount' => 9999.00,
            'base_currency' => 'USD',
            'currency' => 'USD',
            'transaction_date' => $now->copy()->subMonth(),
        ]);

        $response = $this->getJson('/api/dashboard/summary');

        $response->assertOk();
        $this->assertEquals(1750.00, (float) $response->json('data.income_total'));
        $this->assertEquals(500.00, (float) $response->json('data.expense_total'));
    }

    public function test_dashboard_summary_computes_net_worth_from_accounts(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        // Included in net worth
        Account::factory()->create([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'balance' => 5000.00,
            'currency' => 'USD',
            'include_in_net_worth' => true,
        ]);
        Account::factory()->create([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'balance' => 3000.00,
            'currency' => 'USD',
            'include_in_net_worth' => true,
        ]);

        // Excluded from net worth
        Account::factory()->create([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'balance' => 100000.00,
            'currency' => 'USD',
            'include_in_net_worth' => false,
        ]);

        $response = $this->getJson('/api/dashboard/summary');

        $response->assertOk();
        $this->assertEquals(8000.00, (float) $response->json('data.net_worth'));
    }

    public function test_dashboard_summary_returns_last_10_transactions(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        $account = Account::factory()->create([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
        ]);

        Transaction::factory()->count(15)->create([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'account_id' => $account->id,
        ]);

        $response = $this->getJson('/api/dashboard/summary');

        $response->assertOk();
        $this->assertCount(10, $response->json('data.last_10_transactions'));
    }

    public function test_dashboard_summary_returns_empty_stubs_when_no_data(): void
    {
        $user = $this->createUserWithTeam();
        $this->actingAs($user);

        $response = $this->getJson('/api/dashboard/summary');

        $response->assertOk();
        $this->assertEquals(0, (float) $response->json('data.income_total'));
        $this->assertEquals(0, (float) $response->json('data.expense_total'));
        $this->assertEquals(0, (float) $response->json('data.net_worth'));
        $this->assertEmpty($response->json('data.last_10_transactions'));
        $this->assertEmpty($response->json('data.budgets'));
        $this->assertEmpty($response->json('data.upcoming_recurring'));
        $this->assertEmpty($response->json('data.saving_goals'));
    }

    public function test_dashboard_summary_requires_authentication(): void
    {
        $response = $this->getJson('/api/dashboard/summary');

        $response->assertStatus(401);
    }

    public function test_dashboard_summary_team_isolation(): void
    {
        $userA = $this->createUserWithTeam();
        $userB = $this->createUserWithTeam();

        $this->actingAs($userA);
        $accountA = Account::factory()->create([
            'team_id' => $userA->current_team_id,
            'user_id' => $userA->id,
            'balance' => 5000.00,
            'include_in_net_worth' => true,
        ]);

        Transaction::factory()->create([
            'team_id' => $userA->current_team_id,
            'user_id' => $userA->id,
            'account_id' => $accountA->id,
            'type' => TransactionType::Income->value,
            'amount' => 1000.00,
            'base_amount' => 1000.00,
            'base_currency' => 'USD',
            'currency' => 'USD',
            'transaction_date' => Carbon::now(),
        ]);

        $this->actingAs($userB);
        $response = $this->getJson('/api/dashboard/summary');

        $response->assertOk();
        $this->assertEquals(0, (float) $response->json('data.income_total'));
        $this->assertEquals(0, (float) $response->json('data.net_worth'));
    }
}