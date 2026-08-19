<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Billing\BillingController;
use App\Http\Controllers\Billing\CheckoutController;
use App\Http\Controllers\Billing\PortalController;
use App\Http\Controllers\Billing\WebhookController;
use App\Http\Controllers\Api\AccountController;
use App\Models\Account;
use App\Models\Category;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\RecurringTransactionController;
use App\Http\Controllers\Api\BillReminderController;
use App\Http\Controllers\Api\ImportController;

use App\Actions\Dashboard\BuildDashboardSummaryAction;
use App\Http\Controllers\TeamController;

require __DIR__.'/auth.php';

// Auto-login shortcut for development
Route::get('/dev-login', function () {
    Auth::login(\App\Models\User::first());
    return redirect('/dashboard');
});

// Debug page
Route::get('/debug', fn () => inertia('Debug'));

Route::get('/', function () {
    return inertia('Welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function (BuildDashboardSummaryAction $summaryAction) {
        return inertia('Dashboard', [
            'dashboard' => $summaryAction->execute(Auth::user()->current_team_id),
        ]);
    })->name('dashboard');

    // Teams — multi-tenant management (direct DB operations)
    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
    Route::post('/teams/{team}/switch', [TeamController::class, 'switch'])->name('teams.switch');
    Route::post('/teams/{team}/invite', [TeamController::class, 'invite'])->name('teams.invite');
    Route::delete('/teams/{team}/members/{user}', [TeamController::class, 'removeMember'])->name('teams.remove-member');
    Route::delete('/teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');

    // Accounts — direct DB operations (no API controller proxy, same pattern as Categories)
    Route::get('/accounts', function () {
        $accounts = Account::where('team_id', Auth::user()->current_team_id)
            ->orderBy('name')
            ->get();
        return inertia('Accounts/Index', [
            'accounts' => $accounts,
        ]);
    })->name('accounts.index');

    Route::get('/accounts/create', function () {
        return inertia('Accounts/Create');
    })->name('accounts.create');

    Route::post('/accounts', function (Request $request) {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:checking,savings,credit_card,cash,investment'],
            'currency' => ['required', 'string', 'max:3'],
            'initial_balance' => ['nullable', 'numeric', 'min:0'],
            'include_in_net_worth' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'color' => ['nullable', 'string', 'max:7'],
            'icon' => ['nullable', 'string', 'max:50'],
        ]);
        $validated['balance'] = $validated['initial_balance'] ?? 0;
        Account::create($validated);
        return redirect('/accounts')->with('success', 'Akun dibuat');
    })->name('accounts.store');

    Route::get('/accounts/{account}/edit', function (Account $account) {
        return inertia('Accounts/Edit', [
            'account' => $account,
        ]);
    })->name('accounts.edit');

    Route::put('/accounts/{account}', function (Request $request, Account $account) {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'in:checking,savings,credit_card,cash,investment'],
            'currency' => ['sometimes', 'required', 'string', 'max:3'],
            'initial_balance' => ['nullable', 'numeric', 'min:0'],
            'include_in_net_worth' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'color' => ['nullable', 'string', 'max:7'],
            'icon' => ['nullable', 'string', 'max:50'],
        ]);
        $account->update($validated);
        return redirect('/accounts')->with('success', 'Akun diupdate');
    })->name('accounts.update');

    Route::delete('/accounts/{account}', function (Account $account) {
        $account->delete();
        return redirect('/accounts')->with('success', 'Akun dihapus');
    })->name('accounts.destroy');

    // Categories — direct DB operations (no API controller proxy to avoid FormRequest type mismatches)
    Route::get('/categories', function () {
        $categories = Category::with('parent')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        return inertia('Categories/Index', [
            'categories' => $categories,
        ]);
    })->name('categories.index');

    Route::get('/categories/create', function () {
        $parentCategories = Category::orderBy('sort_order')->orderBy('name')->get();
        return inertia('Categories/Create', [
            'parentCategories' => $parentCategories,
        ]);
    })->name('categories.create');

    Route::post('/categories', function (Request $request) {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:income,expense'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'sort_order' => ['nullable', 'integer'],
            'color' => ['nullable', 'string', 'max:7'],
            'icon' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        Category::create($validated);
        return redirect('/categories')->with('success', 'Kategori dibuat');
    })->name('categories.store');

    // Reorder must be before {category} parameterized routes to avoid route collision
    Route::put('/categories/reorder', function (Request $request) {
        $request->validate([
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['integer', 'exists:categories,id'],
        ]);
        foreach ($request->ordered_ids as $index => $id) {
            Category::where('id', $id)->update(['sort_order' => $index + 1]);
        }
        return redirect('/categories')->with('success', 'Kategori diurutkan');
    })->name('categories.reorder');

    Route::get('/categories/{category}/edit', function (Category $category) {
        $category->load('parent');
        $parentCategories = Category::orderBy('sort_order')->orderBy('name')->get();
        return inertia('Categories/Edit', [
            'category' => $category,
            'parentCategories' => $parentCategories,
        ]);
    })->name('categories.edit');

    Route::put('/categories/{category}', function (Request $request, Category $category) {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'in:income,expense'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'sort_order' => ['nullable', 'integer'],
            'color' => ['nullable', 'string', 'max:7'],
            'icon' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $category->update($validated);
        return redirect('/categories')->with('success', 'Kategori diupdate');
    })->name('categories.update');

    Route::delete('/categories/{category}', function (Category $category) {
        $category->delete();
        return redirect('/categories')->with('success', 'Kategori dihapus');
    })->name('categories.destroy');

    // Tags — server-side data via Inertia
    Route::get('/tags', function (TagController $controller) {
        $response = $controller->index();
        $data = $response->getData(true);
        return inertia('Tags/Index', [
            'tags' => $data['data'] ?? [],
        ]);
    })->name('tags.index');

    // Budgets — server-side data via Inertia
    Route::get('/budgets', function (BudgetController $controller) {
        $response = $controller->index();
        $data = $response->getData(true);
        return inertia('Budgets/Index', [
            'budgets' => $data['data'] ?? [],
        ]);
    })->name('budgets');

    // Recurring Transactions — server-side data via Inertia
    Route::get('/recurring-transactions', function (RecurringTransactionController $controller) {
        $response = $controller->index();
        $data = $response->getData(true);
        return inertia('RecurringTransactions/Index', [
            'recurring' => $data['data'] ?? [],
            'upcoming' => $data['upcoming'] ?? [],
        ]);
    })->name('recurring-transactions');

    Route::get('/transactions', function () {
        $user = Auth::user();
        $txns = \App\Models\Transaction::where('team_id', $user->current_team_id)
            ->with(['account:id,name', 'category:id,name,color'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(100)
            ->get();
        return inertia('Transactions/Index', [
            'transactions' => $txns,
        ]);
    })->name('transactions.index');

    Route::get('/transactions/create', function () {
        $user = Auth::user();
        $categories = Category::orderBy('sort_order')->orderBy('name')->get();
        $accounts = Account::where('team_id', $user->current_team_id)
            ->orderBy('name')
            ->get();
        return inertia('Transactions/Create', [
            'categories' => $categories,
            'accounts' => $accounts,
        ]);
    })->name('transactions.create');

    Route::post('/transactions', function (Request $request) {
        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'type' => ['required', 'in:income,expense,transfer'],
            'account_id' => ['required', 'exists:accounts,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'transaction_date' => ['required', 'date'],
            'toko' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
        $validated['team_id'] = Auth::user()->current_team_id;
        $validated['user_id'] = Auth::id();
        \App\Models\Transaction::create($validated);
        return redirect('/transactions')->with('success', 'Transaksi dibuat');
    })->name('transactions.store');

    Route::get('/transactions/{transaction}/edit', function (\App\Models\Transaction $transaction) {
        $user = Auth::user();
        $categories = Category::orderBy('sort_order')->orderBy('name')->get();
        $accounts = Account::where('team_id', $user->current_team_id)
            ->orderBy('name')
            ->get();
        return inertia('Transactions/Edit', [
            'transaction' => $transaction,
            'categories' => $categories,
            'accounts' => $accounts,
        ]);
    })->name('transactions.edit');

    Route::put('/transactions/{transaction}', function (Request $request, \App\Models\Transaction $transaction) {
        $validated = $request->validate([
            'description' => ['sometimes', 'required', 'string', 'max:255'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'type' => ['sometimes', 'required', 'in:income,expense,transfer'],
            'account_id' => ['sometimes', 'required', 'exists:accounts,id'],
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'transaction_date' => ['sometimes', 'required', 'date'],
            'toko' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
        $transaction->update($validated);
        return redirect('/transactions')->with('success', 'Transaksi diupdate');
    })->name('transactions.update');

    Route::delete('/transactions/{transaction}', function (\App\Models\Transaction $transaction) {
        $transaction->delete();
        return redirect('/transactions')->with('success', 'Transaksi dihapus');
    })->name('transactions.destroy');

    // Imports — server-side data via Inertia
    Route::get('/imports', function (ImportController $controller) {
        $response = $controller->index(request());
        $data = $response->getData(true);
        return inertia('Imports/Index', [
            'imports' => $data['data'] ?? [],
            'meta' => $data['meta'] ?? [],
        ]);
    })->name('imports.index');

    Route::get('/imports/create', function () {
        return inertia('Imports/Create');
    })->name('imports.create');

    // Reminders — server-side data via Inertia
    Route::get('/reminders', function (BillReminderController $controller) {
        $remindersResponse = $controller->index();
        $remindersData = $remindersResponse->getData(true);
        $dueSoonResponse = $controller->dueSoon();
        $dueSoonData = $dueSoonResponse->getData(true);
        return inertia('Reminders/Index', [
            'reminders' => $remindersData['data'] ?? [],
            'dueSoon' => $dueSoonData['data'] ?? [],
        ]);
    })->name('reminders');

    Route::get('/goals', fn () => inertia('Goals/Index'))->name('goals');

    Route::get('/reports', fn () => inertia('Reports/Index'))->name('reports');

    Route::get('/settings/billing', [BillingController::class, 'index'])->name('settings.billing');

    Route::get('/settings/currency', function () {
        $user = auth()->user();
        return inertia('Settings/Currency', [
            'currentCurrency' => $user->currency ?? 'IDR',
        ]);
    })->name('settings.currency');

    Route::get('/settings/telegram', function () {
        $user = auth()->user()->load('telegramUser');
        $telegramUser = $user->telegramUser;
        $telegram = ['linked' => false, 'telegram_user' => null];
        if ($telegramUser) {
            $telegram = [
                'linked' => true, 'id' => $telegramUser->id,
                'username' => $telegramUser->username,
                'first_name' => $telegramUser->first_name,
                'is_active' => $telegramUser->is_active,
                'settings' => $telegramUser->settings ?? [],
                'linked_at' => $telegramUser->linked_at?->toISOString(),
            ];
        }
        return inertia('Settings/Telegram', ['telegram' => $telegram]);
    })->name('settings.telegram');

    Route::post('/settings/telegram/generate-link-token', function () {
        $token = \Illuminate\Support\Str::random(64);
        \Illuminate\Support\Facades\Cache::put('telegram_link_token_' . $token, auth()->id(), 600);

        return redirect('/settings/telegram')->with('link_token', $token);
    })->name('settings.telegram.generate-link-token');

    Route::delete('/settings/telegram/unlink', function () {
        $telegramUser = \App\Models\TelegramUser::where('user_id', auth()->id())->first();
        if ($telegramUser) {
            $telegramUser->delete();
        }
        return redirect('/settings/telegram')->with('success', 'Telegram berhasil diputus.');
    })->name('settings.telegram.unlink');

    Route::put('/settings/telegram/settings', function (\Illuminate\Http\Request $request) {
        $telegramUser = \App\Models\TelegramUser::where('user_id', auth()->id())->first();
        if (! $telegramUser) {
            return redirect('/settings/telegram')->with('error', 'Telegram belum terhubung.');
        }

        $validated = $request->validate([
            'daily_summary' => ['sometimes', 'boolean'],
            'budget_alerts' => ['sometimes', 'boolean'],
            'bill_reminders' => ['sometimes', 'boolean'],
        ]);

        $settings = $telegramUser->settings ?? [];
        foreach ($validated as $key => $value) {
            $settings[$key] = $value;
        }
        $telegramUser->update(['settings' => $settings]);

        return redirect('/settings/telegram')->with('success', 'Preferensi tersimpan.');
    })->name('settings.telegram.settings');

    Route::post('/billing/checkout', CheckoutController::class)->name('billing.checkout');
    Route::get('/billing/portal', PortalController::class)->name('billing.portal');

    // Currency update
    Route::put('/settings/currency', function () {
        $user = auth()->user();
        $user->currency = request('currency', 'IDR');
        $user->save();
        return back()->with('success', 'Mata uang berhasil diperbarui');
    })->name('settings.currency.update');
});

Route::post('/webhook/stripe', [WebhookController::class, 'handleWebhook'])->name('webhook.stripe');
