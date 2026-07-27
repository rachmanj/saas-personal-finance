<?php

namespace Tests\Feature;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Enums\CategorizationRuleSource;
use App\Jobs\AutoCategorizeTransaction;
use App\Jobs\BatchAutoCategorize;
use App\Jobs\TrainCategorizationModel;
use App\Models\Account;
use App\Models\AiCategorizationLog;
use App\Models\Category;
use App\Models\CategorizationRule;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AiCategorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Account $account;

    protected Category $foodCategory;

    protected Category $transportCategory;

    protected Category $utilitiesCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->user = User::factory()->create();
        (new CreatePersonalTeamAction)->execute($this->user);

        $this->account = Account::factory()->create([
            'team_id' => $this->user->current_team_id,
            'currency' => 'IDR',
            'balance' => 10000000.00,
        ]);

        $this->foodCategory = Category::factory()->create([
            'team_id' => $this->user->current_team_id,
            'name' => 'Food',
            'type' => 'expense',
        ]);

        $this->transportCategory = Category::factory()->create([
            'team_id' => $this->user->current_team_id,
            'name' => 'Transport',
            'type' => 'expense',
        ]);

        $this->utilitiesCategory = Category::factory()->create([
            'team_id' => $this->user->current_team_id,
            'name' => 'Utilities',
            'type' => 'expense',
        ]);

        $this->actingAs($this->user);
    }

    public function test_single_categorize_uses_matching_rule(): void
    {
        CategorizationRule::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'pattern' => 'starbucks',
            'category_id' => $this->foodCategory->id,
            'confidence' => 0.950,
            'source' => CategorizationRuleSource::Manual,
        ]);

        $transaction = Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'category_id' => null,
            'description' => 'Starbucks coffee morning',
        ]);

        $response = $this->postJson('/api/ai/categorize', [
            'transaction_id' => $transaction->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.transaction_id', $transaction->id)
            ->assertJsonPath('data.predicted_category_id', $this->foodCategory->id)
            ->assertJsonPath('data.confidence', 0.95)
            ->assertJsonPath('data.source', 'rule')
            ->assertJsonPath('data.applied', true);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'category_id' => $this->foodCategory->id,
        ]);

        $this->assertDatabaseHas('ai_categorization_logs', [
            'transaction_id' => $transaction->id,
            'predicted_category_id' => $this->foodCategory->id,
        ]);
    }

    public function test_single_categorize_falls_back_to_ai_stub(): void
    {
        $transaction = Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'category_id' => null,
            'description' => 'Bayar makan siang di warung',
        ]);

        $response = $this->postJson('/api/ai/categorize', [
            'transaction_id' => $transaction->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.predicted_category_id', $this->foodCategory->id)
            ->assertJsonPath('data.source', 'ai')
            ->assertJsonPath('data.applied', true);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'category_id' => $this->foodCategory->id,
        ]);
    }

    public function test_single_categorize_maps_indonesian_transport_keyword(): void
    {
        $transaction = Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'category_id' => null,
            'description' => 'Isi bensin motor',
        ]);

        $response = $this->postJson('/api/ai/categorize', [
            'transaction_id' => $transaction->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.predicted_category_id', $this->transportCategory->id)
            ->assertJsonPath('data.source', 'ai');
    }

    public function test_single_categorize_maps_indonesian_utilities_keyword(): void
    {
        $transaction = Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'category_id' => null,
            'description' => 'Tagihan listrik bulan ini',
        ]);

        $response = $this->postJson('/api/ai/categorize', [
            'transaction_id' => $transaction->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.predicted_category_id', $this->utilitiesCategory->id)
            ->assertJsonPath('data.source', 'ai');
    }

    public function test_higher_confidence_rule_wins_over_lower(): void
    {
        CategorizationRule::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'pattern' => 'grab',
            'category_id' => $this->foodCategory->id,
            'confidence' => 0.600,
            'source' => CategorizationRuleSource::Manual,
        ]);

        CategorizationRule::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'pattern' => 'grab',
            'category_id' => $this->transportCategory->id,
            'confidence' => 0.900,
            'source' => CategorizationRuleSource::Manual,
        ]);

        $transaction = Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'category_id' => null,
            'description' => 'Grab ride to office',
        ]);

        $response = $this->postJson('/api/ai/categorize', [
            'transaction_id' => $transaction->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.predicted_category_id', $this->transportCategory->id)
            ->assertJsonPath('data.confidence', 0.9);
    }

    public function test_low_confidence_prediction_is_not_applied(): void
    {
        CategorizationRule::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'pattern' => 'misc',
            'category_id' => $this->foodCategory->id,
            'confidence' => 0.500,
            'source' => CategorizationRuleSource::Manual,
        ]);

        $transaction = Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'category_id' => null,
            'description' => 'misc purchase',
        ]);

        $response = $this->postJson('/api/ai/categorize', [
            'transaction_id' => $transaction->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.predicted_category_id', $this->foodCategory->id)
            ->assertJsonPath('data.confidence', 0.5)
            ->assertJsonPath('data.applied', false);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'category_id' => null,
        ]);

        $this->assertDatabaseHas('ai_categorization_logs', [
            'transaction_id' => $transaction->id,
            'predicted_category_id' => $this->foodCategory->id,
        ]);
    }

    public function test_batch_categorize_dispatches_jobs_for_uncategorized_transactions(): void
    {
        Queue::fake();

        $uncategorized = Transaction::factory()->count(3)->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'category_id' => null,
        ]);

        Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'category_id' => $this->foodCategory->id,
        ]);

        $response = $this->postJson('/api/ai/categorize/batch');

        $response->assertAccepted()
            ->assertJsonPath('data.dispatched', 3);

        Queue::assertPushed(BatchAutoCategorize::class);
    }

    public function test_batch_auto_categorize_job_dispatches_per_transaction(): void
    {
        Bus::fake([AutoCategorizeTransaction::class]);

        $transactions = Transaction::factory()->count(2)->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'category_id' => null,
        ]);

        $job = new BatchAutoCategorize($this->user->current_team_id);
        $job->handle();

        Bus::assertDispatched(AutoCategorizeTransaction::class, 2);
    }

    public function test_user_can_list_categorization_rules(): void
    {
        CategorizationRule::factory()->count(2)->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'category_id' => $this->foodCategory->id,
        ]);

        $response = $this->getJson('/api/ai/categorization-rules');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_create_categorization_rule(): void
    {
        $response = $this->postJson('/api/ai/categorization-rules', [
            'pattern' => 'netflix',
            'category_id' => $this->foodCategory->id,
            'confidence' => 0.85,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.pattern', 'netflix')
            ->assertJsonPath('data.confidence', 0.85)
            ->assertJsonPath('data.source', 'manual');

        $this->assertDatabaseHas('categorization_rules', [
            'pattern' => 'netflix',
            'category_id' => $this->foodCategory->id,
            'team_id' => $this->user->current_team_id,
            'source' => 'manual',
        ]);
    }

    public function test_user_can_update_categorization_rule(): void
    {
        $rule = CategorizationRule::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'pattern' => 'spotify',
            'category_id' => $this->foodCategory->id,
            'confidence' => 0.800,
        ]);

        $response = $this->putJson("/api/ai/categorization-rules/{$rule->id}", [
            'pattern' => 'spotify premium',
            'category_id' => $this->transportCategory->id,
            'confidence' => 0.95,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.pattern', 'spotify premium')
            ->assertJsonPath('data.category_id', $this->transportCategory->id)
            ->assertJsonPath('data.confidence', 0.95);
    }

    public function test_user_can_delete_categorization_rule(): void
    {
        $rule = CategorizationRule::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'category_id' => $this->foodCategory->id,
        ]);

        $response = $this->deleteJson("/api/ai/categorization-rules/{$rule->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('categorization_rules', [
            'id' => $rule->id,
        ]);
    }

    public function test_categorization_accuracy_endpoint_returns_stats(): void
    {
        $transaction = Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'category_id' => $this->foodCategory->id,
        ]);

        AiCategorizationLog::factory()->create([
            'transaction_id' => $transaction->id,
            'predicted_category_id' => $this->foodCategory->id,
            'actual_category_id' => $this->foodCategory->id,
            'confidence' => 0.900,
            'was_correct' => true,
            'model_version' => 'stub-v1',
        ]);

        AiCategorizationLog::factory()->create([
            'transaction_id' => $transaction->id,
            'predicted_category_id' => $this->transportCategory->id,
            'actual_category_id' => $this->foodCategory->id,
            'confidence' => 0.700,
            'was_correct' => false,
            'model_version' => 'stub-v1',
        ]);

        $response = $this->getJson('/api/ai/categorization-accuracy');

        $response->assertOk()
            ->assertJsonPath('data.total_predictions', 2)
            ->assertJsonPath('data.correct_predictions', 1)
            ->assertJsonPath('data.accuracy_rate', 0.5);
    }

    public function test_train_categorization_model_promotes_corrected_patterns(): void
    {
        $transaction = Transaction::factory()->create([
            'team_id' => $this->user->current_team_id,
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'description' => 'gojek food delivery',
            'category_id' => $this->foodCategory->id,
        ]);

        AiCategorizationLog::factory()->create([
            'transaction_id' => $transaction->id,
            'predicted_category_id' => $this->transportCategory->id,
            'actual_category_id' => $this->foodCategory->id,
            'confidence' => 0.750,
            'was_correct' => false,
            'model_version' => 'stub-v1',
        ]);

        $job = new TrainCategorizationModel($this->user->current_team_id);
        $job->handle();

        $this->assertDatabaseHas('categorization_rules', [
            'team_id' => $this->user->current_team_id,
            'pattern' => 'gojek',
            'category_id' => $this->foodCategory->id,
            'source' => 'ai_trained',
        ]);
    }
}
