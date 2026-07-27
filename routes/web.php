<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::get('/', function () {
    return inertia('Welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return inertia('Dashboard');
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
});
