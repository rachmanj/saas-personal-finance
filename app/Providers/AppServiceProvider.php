<?php

namespace App\Providers;

use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Laravel\Cashier\Cashier;

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
        Cashier::useCustomerModel(Team::class);
        Cashier::ignoreRoutes();

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
