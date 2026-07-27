<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeamContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->current_team_id) {
            abort(403, 'No team context.');
        }

        if ($user && $user->current_team_id) {
            Inertia::share([
                'current_team' => fn () => $user->currentTeam?->only('id', 'name'),
            ]);
        }

        return $next($request);
    }
}
