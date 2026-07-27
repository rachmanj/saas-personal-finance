<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalContribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'saving_goal_id',
        'amount',
        'contributed_at',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'contributed_at' => 'date',
    ];

    public function savingGoal(): BelongsTo
    {
        return $this->belongsTo(SavingGoal::class);
    }
}