<?php

namespace Tests\Feature;

use App\Actions\Budgets\CalculateBudgetUtilizationAction;
use App\Actions\Teams\CreatePersonalTeamAction;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTest extends TestCase
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

    public function test_user_can_list_budgets(): void
    {
        foreach (range(1, 3) as $month) {
            Budget::factory()->create([
                'team_id' => $this->user->current_team_id,
                'user_id' => $this->user->id,
                'category_id' => $this->category->id,
                'start_date' => now()->startOfYear()->addMonths($month - 1)->toDateString(),
            ]);
        }

        $response = $this->getJson('/api/budgets');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_budget(): void
    {
        $data = [
            'category_id' => $this->category->id,
            'amount' => 1000.00,
            'currency' => 'USD',
            'period' => 'monthly',
            'start_date' => now()->startOfMonth()->toDateString(),
            'rollover' => false,
            'notification_threshold' => 80,
        ];

        $response = $this->postJson('/api/budgets', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.amount', '1000.00')
            ->assertJsonPath('data.period', 'monthly');

        $this->assertDatabaseHas('budgets', [
            'category_id' => $this->category->id,
            'amount' => 1000.00,
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_can_view_budget(): void
    {
        $budget = Budget::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson("/api/budgets/{$budget->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $budget->id)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'amount',
                    'utilization' => ['spent', 'remaining', 'percent', 'status'],
                ],
            ]);
    }

    public function test_user_can_update_budget(): void
    {
        $budget = Budget::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->putJson("/api/budgets/{$budget->id}", [
            'amount' => 1500.00,
            'notification_threshold' => 90,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.amount', '1500.00')
            ->assertJsonPath('data.notification_threshold', 90);

        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'amount' => 1500.00,
            'notification_threshold' => 90,
        ]);
    }

    public function test_user_can_delete_budget(): void
    {
        $budget = Budget::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->deleteJson("/api/budgets/{$budget->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('budgets', [
            'id' => $budget->id,
        ]);
    }

    public function test_budget_validation(): void
    {
        $response = $this->postJson('/api/budgets', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'amount', 'currency', 'period', 'start_date']);
    }

    public function test_budget_team_isolation(): void
    {
        $userB = User::factory()->create();
        (new CreatePersonalTeamAction)->execute($userB);

        Budget::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($userB);
        $response = $this->getJson('/api/budgets');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_budget_utilization_ok_status(): void
    {
        $budget = Budget::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'amount' => 1000.00,
            'period' => 'monthly',
            'start_date' => now()->startOfMonth()->toDateString(),
            'notification_threshold' => 80,
        ]);

        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 500.00,
            'base_amount' => 500.00,
            'transaction_date' => now()->toDateString(),
        ]);

        $utilization = (new CalculateBudgetUtilizationAction)->execute($budget);

        $this->assertEquals(500.00, $utilization['spent']);
        $this->assertEquals(500.00, $utilization['remaining']);
        $this->assertEquals(50.0, $utilization['percent']);
        $this->assertEquals('ok', $utilization['status']);
    }

    public function test_budget_utilization_warning_status(): void
    {
        $budget = Budget::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'amount' => 1000.00,
            'period' => 'monthly',
            'start_date' => now()->startOfMonth()->toDateString(),
            'notification_threshold' => 80,
        ]);

        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 850.00,
            'base_amount' => 850.00,
            'transaction_date' => now()->toDateString(),
        ]);

        $utilization = (new CalculateBudgetUtilizationAction)->execute($budget);

        $this->assertEquals(850.00, $utilization['spent']);
        $this->assertEquals(150.00, $utilization['remaining']);
        $this->assertEquals(85.0, $utilization['percent']);
        $this->assertEquals('warning', $utilization['status']);
    }

    public function test_budget_utilization_over_status(): void
    {
        $budget = Budget::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'amount' => 1000.00,
            'period' => 'monthly',
            'start_date' => now()->startOfMonth()->toDateString(),
            'notification_threshold' => 80,
        ]);

        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 1100.00,
            'base_amount' => 1100.00,
            'transaction_date' => now()->toDateString(),
        ]);

        $utilization = (new CalculateBudgetUtilizationAction)->execute($budget);

        $this->assertEquals(1100.00, $utilization['spent']);
        $this->assertEquals(-100.00, $utilization['remaining']);
        $this->assertEquals(110.0, $utilization['percent']);
        $this->assertEquals('over', $utilization['status']);
    }

    public function test_budget_alerts_returns_threshold_budgets(): void
    {
        $warningCategory = Category::factory()->create([
            'team_id' => $this->user->current_team_id,
            'type' => 'expense',
        ]);

        $okCategory = Category::factory()->create([
            'team_id' => $this->user->current_team_id,
            'type' => 'expense',
        ]);

        $warningBudget = Budget::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'category_id' => $warningCategory->id,
            'amount' => 1000.00,
            'period' => 'monthly',
            'start_date' => now()->startOfMonth()->toDateString(),
            'notification_threshold' => 80,
        ]);

        Budget::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'category_id' => $okCategory->id,
            'amount' => 1000.00,
            'period' => 'monthly',
            'start_date' => now()->startOfMonth()->toDateString(),
            'notification_threshold' => 80,
        ]);

        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $warningCategory->id,
            'type' => 'expense',
            'amount' => 850.00,
            'base_amount' => 850.00,
            'transaction_date' => now()->toDateString(),
        ]);

        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $okCategory->id,
            'type' => 'expense',
            'amount' => 200.00,
            'base_amount' => 200.00,
            'transaction_date' => now()->toDateString(),
        ]);

        $response = $this->getJson('/api/budgets/alerts');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $warningBudget->id)
            ->assertJsonPath('data.0.utilization.status', 'warning');
    }

    public function test_custom_period_budget_uses_date_range(): void
    {
        $startDate = now()->subDays(10)->toDateString();
        $endDate = now()->addDays(10)->toDateString();

        $budget = Budget::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'amount' => 500.00,
            'period' => 'custom',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notification_threshold' => 80,
        ]);

        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 100.00,
            'base_amount' => 100.00,
            'transaction_date' => now()->toDateString(),
        ]);

        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 50.00,
            'base_amount' => 50.00,
            'transaction_date' => now()->subDays(20)->toDateString(),
        ]);

        $utilization = (new CalculateBudgetUtilizationAction)->execute($budget);

        $this->assertEquals(100.00, $utilization['spent']);
        $this->assertEquals('ok', $utilization['status']);
    }
}
