# Phase 6: Recurring Transactions Backend Spec

## TDD WORKFLOW: Write test first, verify it FAILS, then implement. ALWAYS TDD.

## 1. Create RecurringTransactionTest (tests/Feature/RecurringTransactionTest.php)

Follow the EXACT pattern from BudgetTest (tests/Feature/BudgetTest.php). Use RefreshDatabase, setUp with withoutVite(), create user+team, actingAs.

### Test methods:
- test_user_can_list_recurring_transactions — create 3, GET /api/recurring-transactions, assert 200 + 3 data
- test_user_can_create_recurring_transaction — POST /api/recurring-transactions with data, assert 201, assertDatabaseHas
- test_user_can_view_recurring_transaction — create, GET /api/recurring-transactions/{id}, assert 200
- test_user_can_update_recurring_transaction — create, PUT /api/recurring-transactions/{id}, assert 200
- test_user_can_delete_recurring_transaction — create, DELETE /api/recurring-transactions/{id}, assert 204
- test_recurring_transaction_validation — POST with empty, assert 422
- test_recurring_transaction_team_isolation — create in userA team, actingAs userB, GET, assert 0 data
- test_next_due_date_is_calculated — create with frequency=monthly, interval=1, start_date=today, assert next_due_date is set correctly
- test_skip_recurring_transaction — create, POST /api/recurring-transactions/{id}/skip, assert 200, assert log was_skipped=true
- test_post_now_recurring_transaction — create, POST /api/recurring-transactions/{id}/post-now, assert 200, assert transaction created, assert log created
- test_upcoming_recurring_transactions — create 2 with next_due_date within 30 days, 1 outside, GET /api/recurring-transactions/upcoming, assert 2 data

## 2. Create RecurringTransaction model + migration + factory + controller

`php artisan make:model RecurringTransaction -mfc --api`

### Migration: create_recurring_transactions_table
Columns:
- id (bigIncrements)
- team_id (foreignId->constrained->cascadeOnDelete)
- user_id (foreignId->constrained->cascadeOnDelete)
- account_id (foreignId->constrained->cascadeOnDelete)
- category_id (foreignId->nullable->constrained->cascadeOnDelete)
- type (enum: income, expense, transfer)
- amount (decimal, 15, 2)
- currency (char, 3)
- description (string)
- frequency (enum: daily, weekly, monthly, yearly, custom)
- interval (unsignedInteger, default 1)
- start_date (date)
- end_date (date, nullable)
- next_due_date (date)
- last_posted_date (date, nullable)
- is_active (boolean, default true)
- template_type (enum: subscription, bill, salary, rent, custom)
- timestamps
- index: team_id, next_due_date

### Model: RecurringTransaction
- use BelongsToTeam, HasFactory
- fillable: user_id, account_id, category_id, type, amount, currency, description, frequency, interval, start_date, end_date, next_due_date, last_posted_date, is_active, template_type
- casts: amount=>decimal:2, start_date=>date, end_date=>date, next_due_date=>date, last_posted_date=>date, is_active=>boolean, interval=>integer, frequency=>string (not enum cast, just string)
- relationships: user(), account(), category(), logs() => HasMany RecurringTransactionLog

### Factory: RecurringTransactionFactory
- team_id: fn() => Team::factory()
- user_id: fn() => User::factory()
- account_id: fn() => Account::factory()
- category_id: null
- type: 'expense'
- amount: fake()->randomFloat(2, 10, 1000)
- currency: 'USD'
- description: fake()->sentence(3)
- frequency: 'monthly'
- interval: 1
- start_date: now()->toDateString()
- next_due_date: now()->toDateString()
- is_active: true
- template_type: 'bill'

## 3. Create RecurringTransactionLog model + migration + factory

`php artisan make:model RecurringTransactionLog -mf`

### Migration: create_recurring_transaction_logs_table
Columns:
- id (bigIncrements)
- recurring_transaction_id (foreignId->constrained->cascadeOnDelete)
- transaction_id (foreignId->nullable->constrained->cascadeOnDelete)
- posted_at (datetime, nullable)
- was_skipped (boolean, default false)
- skip_reason (string, nullable)
- timestamps

### Model: RecurringTransactionLog
- No BelongsToTeam (logs are tied to recurring which is team-scoped)
- fillable: recurring_transaction_id, transaction_id, posted_at, was_skipped, skip_reason
- casts: posted_at=>datetime, was_skipped=>boolean
- relationships: recurringTransaction(), transaction()

### Factory: RecurringTransactionLogFactory
- recurring_transaction_id: fn() => RecurringTransaction::factory()
- was_skipped: false
- transaction_id: null

## 4. Create CalculateNextDueDateAction

File: app/Actions/Recurring/CalculateNextDueDateAction.php

```php
class CalculateNextDueDateAction
{
    public function execute(string $frequency, int $interval, Carbon $fromDate): Carbon
    {
        return match ($frequency) {
            'daily' => $fromDate->copy()->addDays($interval),
            'weekly' => $fromDate->copy()->addWeeks($interval),
            'monthly' => $fromDate->copy()->addMonths($interval),
            'yearly' => $fromDate->copy()->addYears($interval),
            'custom' => $fromDate->copy()->addDays($interval),
            default => $fromDate->copy()->addMonth(),
        };
    }
}
```

## 5. Create PostRecurringTransactionAction

File: app/Actions/Recurring/PostRecurringTransactionAction.php

Inject CreateTransactionAction and CalculateNextDueDateAction via constructor.

execute(RecurringTransaction $recurring): void
- Create Transaction via CreateTransactionAction->execute() with: account_id, user_id, category_id, type, amount, currency, description, transaction_date=now()
- Create RecurringTransactionLog with: recurring_transaction_id, transaction_id, posted_at=now()
- Update RecurringTransaction: last_posted_date=now(), next_due_date=CalculateNextDueDateAction->execute(frequency, interval, next_due_date)

## 6. Create PostRecurringTransactions Job

`php artisan make:job PostRecurringTransactions`

File: app/Jobs/PostRecurringTransactions.php

handle(PostRecurringTransactionAction $action): void
- RecurringTransaction::where('is_active', true)->where('next_due_date', '<=', today())->chunk(100, function ($rows) use ($action) { foreach ($rows as $row) { $action->execute($row); } });

## 7. Create RecurringTransactionController

File: app/Http/Controllers/Api/RecurringTransactionController.php

Follow BudgetController pattern exactly.

Methods:
- index(): GET — list all with category, account eager loaded
- store(StoreRecurringTransactionRequest): POST — create, calculate next_due_date using CalculateNextDueDateAction on start_date
- show(RecurringTransaction): GET — with category, account, logs
- update(UpdateRecurringTransactionRequest, RecurringTransaction): PUT
- destroy(RecurringTransaction): DELETE — 204
- skip(RecurringTransaction): POST /{id}/skip — create RecurringTransactionLog with was_skipped=true, update next_due_date
- postNow(RecurringTransaction): POST /{id}/post-now — use PostRecurringTransactionAction->execute($recurring)
- upcoming(): GET /upcoming — RecurringTransaction::where('is_active', true)->where('next_due_date', '<=', now()->addDays(30))->where('next_due_date', '>=', today())->orderBy('next_due_date')->get()

## 8. Create Form Requests

- StoreRecurringTransactionRequest: validate account_id, category_id (nullable), type (in:income,expense,transfer), amount (numeric min 0.01), currency (size 3), description (nullable), frequency (in:daily,weekly,monthly,yearly,custom), interval (nullable integer min 1), start_date (required date), end_date (nullable date), template_type (nullable in:subscription,bill,salary,rent,custom)
- UpdateRecurringTransactionRequest: same rules but all optional (sometimes)

## 9. Register Routes in routes/api.php

Add inside auth:sanctum group:
```php
Route::prefix('recurring-transactions')->group(function () {
    Route::get('/', [RecurringTransactionController::class, 'index']);
    Route::post('/', [RecurringTransactionController::class, 'store']);
    Route::get('/upcoming', [RecurringTransactionController::class, 'upcoming']);
    Route::get('/{recurringTransaction}', [RecurringTransactionController::class, 'show']);
    Route::put('/{recurringTransaction}', [RecurringTransactionController::class, 'update']);
    Route::delete('/{recurringTransaction}', [RecurringTransactionController::class, 'destroy']);
    Route::post('/{recurringTransaction}/skip', [RecurringTransactionController::class, 'skip']);
    Route::post('/{recurringTransaction}/post-now', [RecurringTransactionController::class, 'postNow']);
});
```

## 10. Register Schedule in routes/console.php

Add:
```php
use App\Jobs\PostRecurringTransactions;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new PostRecurringTransactions)->dailyAt('00:05');
```

## 11. Update Dashboard

Update BuildDashboardSummaryAction to include real budget alerts and upcoming recurring:
- budgets: fetch from Budget::with('category')->get() and use CalculateBudgetUtilizationAction to calculate utilization, filter to warning/over
- upcoming_recurring: fetch from RecurringTransaction::where('is_active', true)->where('next_due_date', '>=', today())->where('next_due_date', '<=', now()->addDays(30))->orderBy('next_due_date')->limit(10)->get()

## IMPORTANT RULES:
- Follow the EXACT same patterns as BudgetController, BudgetTest, StoreBudgetRequest
- Every API response uses { data, message, errors, meta } envelope
- All models with team_id must use BelongsToTeam trait
- TDD: write test FIRST, run php artisan test --filter=RecurringTransaction, see it FAIL, then implement
- After ALL tests pass, run: php artisan test (full suite) to ensure nothing broke
- Commit after: git add -A && git commit -m "feat: Phase 6 — Recurring Transactions backend (TDD)"