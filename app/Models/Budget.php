<?php

namespace App\Models;

use App\Enums\Frequency;
use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use BelongsToTeam, HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'amount',
        'currency',
        'period',
        'start_date',
        'end_date',
        'rollover',
        'notification_threshold',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'rollover' => 'boolean',
        'notification_threshold' => 'integer',
        'period' => Frequency::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
