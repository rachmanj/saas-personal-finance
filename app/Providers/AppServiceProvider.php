<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Inertia::share([
            'auth' => [
                'user' => fn () => Auth::check() ? Auth::user()->only('id', 'name', 'email') : null,
            ],
            'current_team' => fn () => Auth::check() && Auth::user()->currentTeam
                ? Auth::user()->currentTeam->only('id', 'name')
                : null,
            'flash' => fn () => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ]);
    }
}
