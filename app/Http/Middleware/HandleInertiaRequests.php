<?php

namespace App\Http\Middleware;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = Auth::check() ? Auth::user() : null;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'currency' => $user->currency ?? 'IDR',
                ] : null,
            ],
            'current_team' => $user && $user->currentTeam ? [
                'id' => $user->currentTeam->id,
                'name' => $user->currentTeam->name,
            ] : null,
            'teams' => $user ? $this->teamsForUser($user) : [],
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function teamsForUser($user): array
    {
        return $user->allTeams()
            ->withCount('users')
            ->get()
            ->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'personal_team' => (bool) $team->personal_team,
                'member_count' => $team->users_count,
                'role' => $team->pivot->role,
            ])
            ->all();
    }
}
