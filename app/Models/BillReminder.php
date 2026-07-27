<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillReminder extends Model
{
    use BelongsToTeam, HasFactory;

    protected $fillable = [
        'name',
        'amount',
        'currency',
        'due_date',
        'reminder_days_before',
        'is_recurring',
        'frequency',
        'is_paid',
        'paid_at',
        'subscription_slug',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'reminder_days_before' => 'array',
        'is_recurring' => 'boolean',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
