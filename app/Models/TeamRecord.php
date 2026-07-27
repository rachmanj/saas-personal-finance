<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Model;

class TeamRecord extends Model
{
    use BelongsToTeam;

    protected $fillable = ['team_id', 'name'];
}
