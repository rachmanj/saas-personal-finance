<?php

namespace App\Models;

use App\Enums\OcrJobStatus;
use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OcrJob extends Model
{
    use BelongsToTeam, HasFactory;

    protected $fillable = [
        'user_id',
        'file_path',
        'status',
        'result',
        'error_log',
    ];

    protected $casts = [
        'result' => 'array',
        'status' => OcrJobStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
