<?php

namespace App\Models;

use App\Enums\ImportFileType;
use App\Enums\ImportStatus;
use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Import extends Model
{
    use BelongsToTeam, HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
        'account_id',
        'file_path',
        'file_type',
        'status',
        'total_rows',
        'imported_rows',
        'skipped_rows',
        'error_log',
        'column_mapping',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'imported_rows' => 'integer',
        'skipped_rows' => 'integer',
        'error_log' => 'array',
        'column_mapping' => 'array',
        'file_type' => ImportFileType::class,
        'status' => ImportStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
