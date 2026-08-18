<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        return Inertia::render('Teams/Index', [
            'teams' => $this->teamsForUser($user, withMembers: true),
            'current_team_id' => $user->current_team_id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = Auth::user();

        $team = $user->ownedTeams()->create([
            'name' => $validated['name'],
            'personal_team' => false,
        ]);

        $team->users()->attach($user, ['role' => 'owner']);

        $user->forceFill(['current_team_id' => $team->id])->save();

        return redirect('/teams')->with('success', 'Tim berhasil dibuat');
    }

    public function switch(Team $team): RedirectResponse
    {
        $user = Auth::user();
        $this->authorizeMember($user, $team);

        $user->forceFill(['current_team_id' => $team->id])->save();

        return redirect('/dashboard')->with('success', 'Berpindah ke tim '.$team->name);
    }

    public function invite(Request $request, Team $team): RedirectResponse
    {
        $user = Auth::user();
        $this->authorizeOwner($user, $team);

        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => ['nullable', 'in:member,owner'],
        ]);

        $role = $validated['role'] ?? 'member';

        $existing = User::where('email', $validated['email'])->first();

        if ($existing) {
            if (! $team->users()->where('user_id', $existing->id)->exists()) {
                $team->users()->attach($existing, ['role' => $role]);
            }

            return redirect('/teams')->with('success', 'Anggota ditambahkan');
        }

        TeamInvitation::create([
            'team_id' => $team->id,
            'email' => $validated['email'],
            'role' => $role,
        ]);

        return redirect('/teams')->with('success', 'Undangan dibuat');
    }

    public function removeMember(Team $team, User $user): RedirectResponse
    {
        $authUser = Auth::user();
        $this->authorizeOwner($authUser, $team);

        if ($user->id === $authUser->id) {
            abort(403, 'Pemilik tidak dapat menghapus dirinya sendiri.');
        }

        $team->users()->detach($user->id);

        return redirect('/teams')->with('success', 'Anggota dihapus');
    }

    public function destroy(Team $team): RedirectResponse
    {
        $user = Auth::user();
        $this->authorizeOwner($user, $team);

        if ($team->personal_team) {
            abort(403, 'Tim pribadi tidak dapat dihapus.');
        }

        $deletedId = $team->id;
        $team->delete();

        if ($user->current_team_id === $deletedId) {
            $fallback = $user->ownedTeams()->where('personal_team', true)->first()
                ?? $user->allTeams()->first();
            $user->forceFill(['current_team_id' => $fallback?->id])->save();
        }

        return redirect('/teams')->with('success', 'Tim dihapus');
    }

    private function authorizeMember(User $user, Team $team): void
    {
        if (! $team->users()->where('user_id', $user->id)->exists()) {
            abort(403, 'Anda bukan anggota tim ini.');
        }
    }

    private function authorizeOwner(User $user, Team $team): void
    {
        $membership = $team->users()->where('user_id', $user->id)->first();

        if (! $membership || $membership->pivot->role !== 'owner') {
            abort(403, 'Hanya pemilik yang dapat melakukan ini.');
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function teamsForUser(User $user, bool $withMembers = false): array
    {
        $query = $user->allTeams()->withCount('users');

        if ($withMembers) {
            $query->with('users:id,name,email');
        }

        return $query->get()->map(function (Team $team) use ($withMembers): array {
            $data = [
                'id' => $team->id,
                'name' => $team->name,
                'personal_team' => (bool) $team->personal_team,
                'member_count' => $team->users_count,
                'role' => $team->pivot->role,
            ];

            if ($withMembers) {
                $data['members'] = $team->users->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->pivot->role,
                ])->values();
            }

            return $data;
        })->all();
    }
}
