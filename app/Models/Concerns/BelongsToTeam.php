<?php

namespace App\Models\Concerns;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToTeam
{
    protected static function bootBelongsToTeam(): void
    {
        static::addGlobalScope('team', function (Builder $builder) {
            if (Auth::check() && Auth::user()->current_team_id) {
                $builder->where($builder->getModel()->getTable().'.team_id', Auth::user()->current_team_id);
            }
        });

        static::creating(function ($model) {
            if (Auth::check() && empty($model->team_id)) {
                $model->team_id = Auth::user()->current_team_id;
            }
        });
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
