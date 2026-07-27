<?php

namespace App\Models;

use App\Enums\CategorizationRuleSource;
use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategorizationRule extends Model
{
    use BelongsToTeam, HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
        'pattern',
        'category_id',
        'confidence',
        'source',
    ];

    protected $casts = [
        'confidence' => 'float',
        'source' => CategorizationRuleSource::class,
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
