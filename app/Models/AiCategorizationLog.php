<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiCategorizationLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'predicted_category_id',
        'confidence',
        'actual_category_id',
        'was_correct',
        'model_version',
        'created_at',
    ];

    protected $casts = [
        'confidence' => 'float',
        'was_correct' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function predictedCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'predicted_category_id');
    }

    public function actualCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'actual_category_id');
    }
}
