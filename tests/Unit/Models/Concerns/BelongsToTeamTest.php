<?php

namespace Tests\Unit\Models\Concerns;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Models\TeamRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BelongsToTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_only_see_records_from_their_current_team(): void
    {
        $action = new CreatePersonalTeamAction;

        $user1 = User::factory()->create();
        $team1 = $action->execute($user1);

        $user2 = User::factory()->create();
        $team2 = $action->execute($user2);

        TeamRecord::withoutGlobalScope('team')->create([
            'team_id' => $team1->id,
            'name' => 'Team 1 Record',
        ]);

        TeamRecord::withoutGlobalScope('team')->create([
            'team_id' => $team2->id,
            'name' => 'Team 2 Record',
        ]);

        $this->actingAs($user1);

        $records = TeamRecord::all();

        $this->assertCount(1, $records);
        $this->assertSame($team1->id, $records->first()->team_id);
        $this->assertSame('Team 1 Record', $records->first()->name);

        $this->actingAs($user2);

        $records = TeamRecord::all();

        $this->assertCount(1, $records);
        $this->assertSame($team2->id, $records->first()->team_id);
        $this->assertSame('Team 2 Record', $records->first()->name);
    }

    public function test_team_id_is_auto_filled_on_create(): void
    {
        $action = new CreatePersonalTeamAction;

        $user = User::factory()->create();
        $team = $action->execute($user);

        $this->actingAs($user);

        $record = TeamRecord::create(['name' => 'Auto-filled record']);

        $this->assertSame($team->id, $record->team_id);
    }
}
