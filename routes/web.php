<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Billing\BillingController;
use App\Http\Controllers\Billing\CheckoutController;
use App\Http\Controllers\Billing\PortalController;
use App\Http\Controllers\Billing\WebhookController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\CategoryController;
use App\Models\Category;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\RecurringTransactionController;
use App\Http\Controllers\Api\BillReminderController;
use App\Http\Controllers\Api\ImportController;

use App\Actions\Dashboard\BuildDashboardSummaryAction;

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

    // Accounts — server-side data via Inertia
    Route::get('/accounts', function (AccountController $controller) {
        $request = request()->merge(['includeInactive' => true]);
        $response = $controller->index($request);
        $data = $response->getData(true);
        return inertia('Accounts/Index', [
            'accounts' => $data['data'] ?? [],
        ]);
    })->name('accounts.index');

    Route::get('/accounts/create', function () {
        return inertia('Accounts/Create');
    })->name('accounts.create');

    Route::get('/accounts/{id}/edit', function (string $id) {
        return inertia('Accounts/Edit', ['id' => $id]);
    })->name('accounts.edit');

    // Categories — server-side data via Inertia
    Route::get('/categories', function (CategoryController $controller) {
        $response = $controller->index();
        $data = $response->getData(true);
        return inertia('Categories/Index', [
            'categories' => $data['data'] ?? [],
        ]);
    })->name('categories.index');

    Route::get('/categories/create', function (CategoryController $controller) {
        $response = $controller->index();
        $data = $response->getData(true);
        return inertia('Categories/Create', [
            'parentCategories' => $data['data'] ?? [],
        ]);
    })->name('categories.create');

    Route::post('/categories', function (Request $request, CategoryController $controller) {
        $controller->store($request);
        return redirect('/categories')->with('success', 'Kategori dibuat');
    })->name('categories.store');

    Route::get('/categories/{category}/edit', function (CategoryController $controller, Category $category) {
        $categoryData = $controller->show($category)->getData(true)['data'] ?? [];
        $allResponse = $controller->index();
        $allData = $allResponse->getData(true);
        return inertia('Categories/Edit', [
            'category' => $categoryData,
            'parentCategories' => $allData['data'] ?? [],
        ]);
    })->name('categories.edit');

    Route::put('/categories/{category}', function (CategoryController $controller, Request $request, Category $category) {
        $controller->update($request, $category);
        return redirect('/categories')->with('success', 'Kategori diupdate');
    })->name('categories.update');

    Route::delete('/categories/{category}', function (CategoryController $controller, Category $category) {
        $controller->destroy($category);
        return redirect('/categories')->with('success', 'Kategori dihapus');
    })->name('categories.destroy');

    Route::put('/categories/reorder', function (Request $request, CategoryController $controller) {
        $controller->reorder($request);
        return redirect('/categories')->with('success', 'Kategori diurutkan');
    })->name('categories.reorder');

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

    Route::get('/transactions/{id}', function (string $id) {
        return inertia('Transactions/Show', ['id' => $id]);
    })->name('transactions.show');

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
