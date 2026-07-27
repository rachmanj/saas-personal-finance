<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\OcrTransactionController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\VoiceTransactionController;
use App\Http\Controllers\Api\TransactionBulkController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\TransactionSplitController;
use App\Http\Controllers\Api\TransactionSuggestionController;
use Illuminate\Support\Facades\Route;

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
});
