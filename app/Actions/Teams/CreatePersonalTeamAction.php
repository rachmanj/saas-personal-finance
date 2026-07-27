<?php

namespace App\Actions\Teams;

use App\Models\Team;
use App\Models\User;

class CreatePersonalTeamAction
{
    public function execute(User $user): Team
    {
        $team = $user->ownedTeams()->create([
            'name' => $user->name.'\'s Team',
            'personal_team' => true,
        ]);

        $team->users()->attach($user, ['role' => 'owner']);

        $user->forceFill(['current_team_id' => $team->id])->save();

        return $team;
    }
}
