<?php

namespace App\Models;

use App\Enums\VoiceJobStatus;
use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoiceJob extends Model
{
    use BelongsToTeam, HasFactory;

    protected $fillable = [
        'user_id',
        'audio_path',
        'transcript',
        'status',
        'result',
        'error_log',
    ];

    protected $casts = [
        'result' => 'array',
        'status' => VoiceJobStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
