<?php

namespace Tests\Unit\Actions;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Actions\Transactions\CreateTransactionAction;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CurrencyConverterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CreateTransactionActionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Account $account;
    protected Account $toAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->user = User::factory()->create();
        (new CreatePersonalTeamAction)->execute($this->user);

        $this->account = Account::factory()->create([
            'team_id' => $this->user->current_team_id,
            'currency' => 'USD',
            'balance' => 1000.00,
        ]);

        $this->toAccount = Account::factory()->create([
            'team_id' => $this->user->current_team_id,
            'currency' => 'USD',
            'balance' => 500.00,
        ]);

        $this->actingAs($this->user);
    }

    public function test_income_transaction_increases_account_balance(): void
    {
        $action = new CreateTransactionAction(new CurrencyConverterService);

        $data = [
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'type' => 'income',
            'amount' => 100.00,
            'currency' => 'USD',
            'description' => 'Salary',
            'transaction_date' => now()->toDateString(),
            'source' => 'manual',
        ];

        $transaction = $action->execute($data);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals('income', $transaction->type->value);
        $this->assertEquals(100.00, $transaction->amount);

        $this->account->refresh();
        $this->assertEquals(1100.00, $this->account->balance);
    }

    public function test_expense_transaction_decreases_account_balance(): void
    {
        $action = new CreateTransactionAction(new CurrencyConverterService);

        $data = [
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'type' => 'expense',
            'amount' => 50.00,
            'currency' => 'USD',
            'description' => 'Groceries',
            'transaction_date' => now()->toDateString(),
            'source' => 'manual',
        ];

        $transaction = $action->execute($data);

        $this->assertEquals('expense', $transaction->type->value);

        $this->account->refresh();
        $this->assertEquals(950.00, $this->account->balance);
    }

    public function test_transfer_moves_balance_between_accounts(): void
    {
        $action = new CreateTransactionAction(new CurrencyConverterService);

        $data = [
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'to_account_id' => $this->toAccount->id,
            'type' => 'transfer',
            'amount' => 200.00,
            'currency' => 'USD',
            'description' => 'Transfer to savings',
            'transaction_date' => now()->toDateString(),
            'source' => 'manual',
        ];

        $transaction = $action->execute($data);

        $this->assertEquals('transfer', $transaction->type->value);
        $this->assertEquals($this->toAccount->id, $transaction->to_account_id);

        $this->account->refresh();
        $this->toAccount->refresh();

        $this->assertEquals(800.00, $this->account->balance);
        $this->assertEquals(700.00, $this->toAccount->balance);
    }

    public function test_transaction_creates_splits_when_provided(): void
    {
        $action = new CreateTransactionAction(new CurrencyConverterService);

        $data = [
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'type' => 'expense',
            'amount' => 100.00,
            'currency' => 'USD',
            'description' => 'Split purchase',
            'transaction_date' => now()->toDateString(),
            'source' => 'manual',
            'splits' => [
                ['category_id' => null, 'amount' => 60.00, 'description' => 'Food'],
                ['category_id' => null, 'amount' => 40.00, 'description' => 'Drinks'],
            ],
        ];

        $transaction = $action->execute($data);

        $this->assertCount(2, $transaction->splits);
        $this->assertEquals(60.00, $transaction->splits[0]->amount);
        $this->assertEquals(40.00, $transaction->splits[1]->amount);
    }

    public function test_transaction_syncs_tags_when_provided(): void
    {
        $tag1 = \App\Models\Tag::factory()->create(['team_id' => $this->user->current_team_id]);
        $tag2 = \App\Models\Tag::factory()->create(['team_id' => $this->user->current_team_id]);

        $action = new CreateTransactionAction(new CurrencyConverterService);

        $data = [
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'type' => 'expense',
            'amount' => 50.00,
            'currency' => 'USD',
            'description' => 'Tagged transaction',
            'transaction_date' => now()->toDateString(),
            'source' => 'manual',
            'tag_ids' => [$tag1->id, $tag2->id],
        ];

        $transaction = $action->execute($data);

        $this->assertCount(2, $transaction->tags);
        $this->assertTrue($transaction->tags->contains($tag1));
        $this->assertTrue($transaction->tags->contains($tag2));
    }

    public function test_base_amount_is_calculated_with_exchange_rate(): void
    {
        // Override the converter to return a specific rate
        $converter = Mockery::mock(CurrencyConverterService::class);
        $converter->shouldReceive('rateFor')
            ->with('EUR', 'USD', Mockery::any())
            ->andReturn(0.85);

        $action = new CreateTransactionAction($converter);

        $data = [
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'type' => 'income',
            'amount' => 100.00,
            'currency' => 'EUR',
            'description' => 'Euro income',
            'transaction_date' => now()->toDateString(),
            'source' => 'manual',
            'base_currency' => 'USD',
        ];

        $transaction = $action->execute($data);

        $this->assertEquals(85.00, $transaction->base_amount);
        $this->assertEquals(0.85, $transaction->exchange_rate);
        $this->assertEquals('USD', $transaction->base_currency);
    }
}
