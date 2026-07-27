<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringTransactionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'recurring_transaction_id',
        'transaction_id',
        'posted_at',
        'was_skipped',
        'skip_reason',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
        'was_skipped' => 'boolean',
    ];

    public function recurringTransaction(): BelongsTo
    {
        return $this->belongsTo(RecurringTransaction::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
