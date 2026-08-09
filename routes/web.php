<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Billing\BillingController;
use App\Http\Controllers\Billing\CheckoutController;
use App\Http\Controllers\Billing\PortalController;
use App\Http\Controllers\Billing\WebhookController;

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

    Route::get('/accounts', function () {
        return inertia('Accounts/Index');
    })->name('accounts.index');

    Route::get('/accounts/create', function () {
        return inertia('Accounts/Create');
    })->name('accounts.create');

    Route::get('/accounts/{id}/edit', function (string $id) {
        return inertia('Accounts/Edit', ['id' => $id]);
    })->name('accounts.edit');

    Route::get('/categories', function () {
        return inertia('Categories/Index');
    })->name('categories.index');

    Route::get('/categories/create', function () {
        return inertia('Categories/Create');
    })->name('categories.create');

    Route::get('/categories/{id}/edit', function (string $id) {
        return inertia('Categories/Edit', ['id' => $id]);
    })->name('categories.edit');

    Route::get('/tags', function () {
        return inertia('Tags/Index');
    })->name('tags.index');

    Route::get('/budgets', fn () => inertia('Budgets/Index'))->name('budgets');

    Route::get('/recurring-transactions', fn () => inertia('RecurringTransactions/Index'))->name('recurring-transactions');

    Route::get('/transactions', function () {
        return inertia('Transactions/Index');
    })->name('transactions.index');

    Route::get('/transactions/{id}', function (string $id) {
        return inertia('Transactions/Show', ['id' => $id]);
    })->name('transactions.show');

    Route::get('/imports', function () {
        return inertia('Imports/Index');
    })->name('imports.index');

    Route::get('/imports/create', function () {
        return inertia('Imports/Create');
    })->name('imports.create');

    Route::get('/reminders', fn () => inertia('Reminders/Index'))->name('reminders');

    Route::get('/reports', fn () => inertia('Reports/Index'))->name('reports');

    Route::get('/settings/billing', [BillingController::class, 'index'])->name('settings.billing');
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
});

Route::post('/webhook/stripe', [WebhookController::class, 'handleWebhook'])->name('webhook.stripe');
