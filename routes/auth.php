<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [RegisteredUserController::class, 'store']);
