<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavingGoal extends Model
{
    use BelongsToTeam, HasFactory;

    protected $fillable = [
        'user_id',
        'account_id',
        'name',
        'target_amount',
        'current_amount',
        'currency',
        'deadline',
        'auto_contribution_amount',
        'auto_contribution_frequency',
        'is_completed',
        'completed_at',
        'color',
        'icon',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'auto_contribution_amount' => 'decimal:2',
        'deadline' => 'date',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(GoalContribution::class);
    }

    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_completed', false);
    }
}