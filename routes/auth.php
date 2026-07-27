<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
    Route::get('/register', function () {
        return Inertia::render('Auth/Register');
    })->name('register');

    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'login']);

    Route::get('/forgot-password', function () {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    })->name('password.request');

    Route::get('/reset-password/{token}', function (string $token) {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => request()->query('email', ''),
        ]);
    })->name('password.reset');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');
