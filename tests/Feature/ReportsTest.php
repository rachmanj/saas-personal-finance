<?php

namespace Tests\Feature;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Account $account;

    protected Category $foodCategory;

    protected Category $salaryCategory;

    protected Category $transportCategory;

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

        $this->foodCategory = Category::factory()->create([
            'team_id' => $this->user->current_team_id,
            'type' => 'expense',
            'name' => 'Food',
        ]);

        $this->salaryCategory = Category::factory()->create([
            'team_id' => $this->user->current_team_id,
            'type' => 'income',
            'name' => 'Salary',
        ]);

        $this->transportCategory = Category::factory()->create([
            'team_id' => $this->user->current_team_id,
            'type' => 'expense',
            'name' => 'Transport',
        ]);

        $this->actingAs($this->user);
    }

    public function test_spending_by_category_returns_aggregated_data(): void
    {
        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'category_id' => $this->foodCategory->id,
            'type' => 'expense',
            'amount' => 150.00,
            'transaction_date' => '2026-07-01',
        ]);

        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'category_id' => $this->foodCategory->id,
            'type' => 'expense',
            'amount' => 50.00,
            'transaction_date' => '2026-07-10',
        ]);

        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'category_id' => $this->transportCategory->id,
            'type' => 'expense',
            'amount' => 80.00,
            'transaction_date' => '2026-07-05',
        ]);

        $response = $this->getJson('/api/reports/spending-by-category?start_date=2026-07-01&end_date=2026-07-31');

        $response->assertOk()
            ->assertJsonStructure(['data', 'message', 'errors', 'meta'])
            ->assertJsonFragment(['name' => 'Food'])
            ->assertJsonFragment(['total' => '200.00'])
            ->assertJsonFragment(['name' => 'Transport'])
            ->assertJsonFragment(['total' => '80.00']);
    }

    public function test_income_vs_expense_returns_aggregated_totals(): void
    {
        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'category_id' => $this->salaryCategory->id,
            'type' => 'income',
            'amount' => 5000.00,
            'transaction_date' => '2026-07-01',
        ]);

        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'category_id' => $this->foodCategory->id,
            'type' => 'expense',
            'amount' => 200.00,
            'transaction_date' => '2026-07-05',
        ]);

        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'category_id' => $this->transportCategory->id,
            'type' => 'expense',
            'amount' => 80.00,
            'transaction_date' => '2026-07-10',
        ]);

        $response = $this->getJson('/api/reports/income-vs-expense?start_date=2026-07-01&end_date=2026-07-31');

        $response->assertOk()
            ->assertJsonStructure(['data', 'message', 'errors', 'meta'])
            ->assertJsonPath('data.total_income', '5000.00')
            ->assertJsonPath('data.total_expense', '280.00')
            ->assertJsonPath('data.net', '4720.00');
    }

    public function test_monthly_summary_returns_monthly_aggregation(): void
    {
        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'category_id' => $this->foodCategory->id,
            'type' => 'expense',
            'amount' => 100.00,
            'transaction_date' => '2026-06-15',
        ]);

        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'category_id' => $this->foodCategory->id,
            'type' => 'expense',
            'amount' => 200.00,
            'transaction_date' => '2026-07-20',
        ]);

        $response = $this->getJson('/api/reports/monthly-summary?year=2026');

        $response->assertOk()
            ->assertJsonStructure(['data', 'message', 'errors', 'meta']);

        $data = $response->json('data');
        $this->assertCount(12, $data); // 12 months

        // June (index 5) should have 100.00 expense
        $this->assertEquals('100.00', $data[5]['expense']);
        $this->assertEquals('0.00', $data[5]['income']);

        // July (index 6) should have 200.00 expense
        $this->assertEquals('200.00', $data[6]['expense']);
        $this->assertEquals('0.00', $data[6]['income']);
    }

    public function test_trend_returns_daily_trend_data(): void
    {
        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'category_id' => $this->foodCategory->id,
            'type' => 'expense',
            'amount' => 50.00,
            'transaction_date' => '2026-07-01',
        ]);

        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'category_id' => $this->foodCategory->id,
            'type' => 'expense',
            'amount' => 30.00,
            'transaction_date' => '2026-07-01',
        ]);

        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'category_id' => $this->transportCategory->id,
            'type' => 'expense',
            'amount' => 20.00,
            'transaction_date' => '2026-07-02',
        ]);

        $response = $this->getJson('/api/reports/trend?start_date=2026-07-01&end_date=2026-07-02');

        $response->assertOk()
            ->assertJsonStructure(['data', 'message', 'errors', 'meta']);

        $data = $response->json('data');
        $this->assertCount(2, $data);

        $this->assertEquals('80.00', $data[0]['total']);
        $this->assertEquals('2026-07-01', $data[0]['date']);
        $this->assertEquals('20.00', $data[1]['total']);
        $this->assertEquals('2026-07-02', $data[1]['date']);
    }

    public function test_year_over_year_returns_comparison_data(): void
    {
        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'category_id' => $this->foodCategory->id,
            'type' => 'expense',
            'amount' => 100.00,
            'transaction_date' => '2026-07-15',
        ]);

        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'category_id' => $this->salaryCategory->id,
            'type' => 'income',
            'amount' => 5000.00,
            'transaction_date' => '2026-07-01',
        ]);

        $response = $this->getJson('/api/reports/year-over-year?year=2026');

        $response->assertOk()
            ->assertJsonStructure(['data', 'message', 'errors', 'meta']);

        $data = $response->json('data');
        $this->assertArrayHasKey('current_year', $data);
        $this->assertArrayHasKey('previous_year', $data);
        $this->assertArrayHasKey('months', $data);
    }

    public function test_net_worth_returns_account_balances(): void
    {
        Account::factory()->create([
            'team_id' => $this->user->current_team_id,
            'currency' => 'USD',
            'balance' => 5000.00,
            'include_in_net_worth' => true,
        ]);

        Account::factory()->create([
            'team_id' => $this->user->current_team_id,
            'currency' => 'USD',
            'balance' => 2000.00,
            'include_in_net_worth' => false,
        ]);

        $this->account->update([
            'balance' => 10000.00,
            'include_in_net_worth' => true,
        ]);

        $response = $this->getJson('/api/reports/net-worth');

        $response->assertOk()
            ->assertJsonStructure(['data', 'message', 'errors', 'meta']);

        $this->assertEquals('15000.00', $response->json('data.total_net_worth'));
        $this->assertCount(2, $response->json('data.accounts'));
    }

    public function test_reports_are_team_isolated(): void
    {
        // Create another team's user
        $otherUser = User::factory()->create();
        (new CreatePersonalTeamAction)->execute($otherUser);
        $otherUser->refresh();

        $otherAccount = Account::factory()->create([
            'team_id' => $otherUser->current_team_id,
            'currency' => 'USD',
            'balance' => 5000.00,
        ]);

        $otherCategory = Category::factory()->create([
            'team_id' => $otherUser->current_team_id,
            'type' => 'expense',
            'name' => 'Other Team Expense',
        ]);

        Transaction::factory()->create([
            'team_id' => $otherUser->current_team_id,
            'account_id' => $otherAccount->id,
            'category_id' => $otherCategory->id,
            'type' => 'expense',
            'amount' => 999.00,
            'transaction_date' => '2026-07-01',
        ]);

        // Our team's data
        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'category_id' => $this->foodCategory->id,
            'type' => 'expense',
            'amount' => 50.00,
            'transaction_date' => '2026-07-01',
        ]);

        $response = $this->getJson('/api/reports/spending-by-category?start_date=2026-07-01&end_date=2026-07-31');

        $response->assertOk();
        $data = $response->json('data');

        // Should only see our team's category, not the other team's
        $names = array_column($data, 'name');
        $this->assertContains('Food', $names);
        $this->assertNotContains('Other Team Expense', $names);
    }
}