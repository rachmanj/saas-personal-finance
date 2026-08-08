<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\OcrTransactionController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\VoiceTransactionController;
use App\Http\Controllers\Api\TransactionBulkController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\TransactionSplitController;
use App\Http\Controllers\Api\TransactionSuggestionController;
use App\Http\Controllers\Api\RecurringTransactionController;
use App\Http\Controllers\Api\SavingGoalController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\BillReminderController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\AiCategorizationController;
use Illuminate\Support\Facades\Route;

// Public — Telegram webhook (no auth, validated by X-Telegram-Bot-Api-Secret-Token header)
Route::post('/telegram/webhook', [\App\Http\Controllers\Api\TelegramWebhookController::class, 'handle'])
    ->name('telegram.webhook');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('accounts')->group(function () {
        Route::get('/', [AccountController::class, 'index']);
        Route::post('/', [AccountController::class, 'store']);
        Route::put('/{account}/reconcile', [AccountController::class, 'reconcile']);
        Route::get('/{account}', [AccountController::class, 'show']);
        Route::put('/{account}', [AccountController::class, 'update']);
        Route::delete('/{account}', [AccountController::class, 'destroy']);
    });

    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::put('/reorder', [CategoryController::class, 'reorder']);
        Route::get('/{category}', [CategoryController::class, 'show']);
        Route::put('/{category}', [CategoryController::class, 'update']);
        Route::delete('/{category}', [CategoryController::class, 'destroy']);
    });

    Route::prefix('tags')->group(function () {
        Route::get('/', [TagController::class, 'index']);
        Route::post('/', [TagController::class, 'store']);
        Route::get('/{tag}', [TagController::class, 'show']);
        Route::put('/{tag}', [TagController::class, 'update']);
        Route::delete('/{tag}', [TagController::class, 'destroy']);
    });

    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index']);
        Route::post('/', [TransactionController::class, 'store']);
        Route::post('/bulk', TransactionBulkController::class);
        Route::post('/suggestions', TransactionSuggestionController::class);
        Route::post('/ocr', [OcrTransactionController::class, 'store']);
        Route::get('/ocr/{ocrJob}/status', [OcrTransactionController::class, 'status']);
        Route::post('/voice', [VoiceTransactionController::class, 'store']);
        Route::get('/voice/{voiceJob}/status', [VoiceTransactionController::class, 'status']);
        Route::get('/{transaction}', [TransactionController::class, 'show']);
        Route::put('/{transaction}', [TransactionController::class, 'update']);
        Route::delete('/{transaction}', [TransactionController::class, 'destroy']);
        Route::post('/{transaction}/splits', [TransactionSplitController::class, 'store']);
        Route::delete('/{transaction}/splits/{split}', [TransactionSplitController::class, 'destroy']);
    });

    Route::prefix('dashboard')->group(function () {
        Route::get('/summary', [DashboardController::class, 'summary']);
    });

    Route::prefix('budgets')->group(function () {
        Route::get('/', [BudgetController::class, 'index']);
        Route::post('/', [BudgetController::class, 'store']);
        Route::get('/alerts', [BudgetController::class, 'alerts']);
        Route::get('/{budget}', [BudgetController::class, 'show']);
        Route::put('/{budget}', [BudgetController::class, 'update']);
        Route::delete('/{budget}', [BudgetController::class, 'destroy']);
    });

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

    Route::prefix('imports')->group(function () {
        Route::get('/', [ImportController::class, 'index']);
        Route::post('/upload', [ImportController::class, 'upload']);
        Route::get('/{import}', [ImportController::class, 'show']);
        Route::get('/{import}/preview', [ImportController::class, 'preview']);
        Route::post('/{import}/process', [ImportController::class, 'process']);
        Route::post('/{import}/confirm', [ImportController::class, 'confirm']);
        Route::delete('/{import}', [ImportController::class, 'destroy']);
    });

    Route::prefix('saving-goals')->group(function () {
        Route::get('/', [SavingGoalController::class, 'index']);
        Route::post('/', [SavingGoalController::class, 'store']);
        Route::get('/{savingGoal}', [SavingGoalController::class, 'show']);
        Route::put('/{savingGoal}', [SavingGoalController::class, 'update']);
        Route::delete('/{savingGoal}', [SavingGoalController::class, 'destroy']);
        Route::post('/{savingGoal}/contributions', [SavingGoalController::class, 'addContribution']);
    });

    Route::prefix('reports')->group(function () {
        Route::get('/spending-by-category', [ReportController::class, 'spendingByCategory']);
        Route::get('/income-vs-expense', [ReportController::class, 'incomeVsExpense']);
        Route::get('/monthly-summary', [ReportController::class, 'monthlySummary']);
        Route::get('/trend', [ReportController::class, 'trend']);
        Route::get('/year-over-year', [ReportController::class, 'yearOverYear']);
        Route::get('/net-worth', [ReportController::class, 'netWorth']);
    });

    Route::prefix('bill-reminders')->group(function () {
        Route::get('/', [BillReminderController::class, 'index']);
        Route::post('/', [BillReminderController::class, 'store']);
        Route::get('/due-soon', [BillReminderController::class, 'dueSoon']);
        Route::post('/subscribe', [BillReminderController::class, 'subscribe']);
        Route::get('/{billReminder}', [BillReminderController::class, 'show']);
        Route::put('/{billReminder}', [BillReminderController::class, 'update']);
        Route::delete('/{billReminder}', [BillReminderController::class, 'destroy']);
        Route::put('/{billReminder}/paid', [BillReminderController::class, 'paid']);
    });

    Route::prefix('ai')->group(function () {
        Route::post('/categorize', [AiCategorizationController::class, 'categorize']);
        Route::post('/categorize/batch', [AiCategorizationController::class, 'batchCategorize']);
        Route::get('/categorization-rules', [AiCategorizationController::class, 'indexRules']);
        Route::post('/categorization-rules', [AiCategorizationController::class, 'storeRule']);
        Route::put('/categorization-rules/{categorizationRule}', [AiCategorizationController::class, 'updateRule']);
        Route::delete('/categorization-rules/{categorizationRule}', [AiCategorizationController::class, 'destroyRule']);
        Route::get('/categorization-accuracy', [AiCategorizationController::class, 'accuracy']);
    });

    Route::prefix('export')->group(function () {
        Route::get('/pdf', [ExportController::class, 'pdf']);
        Route::get('/csv', [ExportController::class, 'csv']);
        Route::get('/ofx', [ExportController::class, 'ofx']);
        Route::post('/google-sheets', [ExportController::class, 'googleSheets']);
        Route::get('/download/{type}/{filename}', [ExportController::class, 'download']);
    });
});
