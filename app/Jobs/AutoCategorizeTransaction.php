<?php

namespace App\Jobs;

use App\Models\AiCategorizationLog;
use App\Models\Transaction;
use App\Services\CategorizationRuleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AutoCategorizeTransaction implements ShouldQueue
{
    use Queueable;

    public function __construct(public Transaction $transaction) {}

    public function handle(CategorizationRuleService $ruleService): void
    {
        if ($this->transaction->category_id !== null) {
            return;
        }

        $suggestion = $ruleService->suggest(
            $this->transaction->description ?? '',
        );

        $minConfidence = config('categorization.min_confidence', 0.7);
        $applied = $suggestion['category_id'] !== null
            && $suggestion['confidence'] >= $minConfidence;

        if ($applied) {
            $this->transaction->update(['category_id' => $suggestion['category_id']]);
        }

        AiCategorizationLog::create([
            'transaction_id' => $this->transaction->id,
            'predicted_category_id' => $suggestion['category_id'],
            'confidence' => $suggestion['confidence'],
            'actual_category_id' => $applied ? $suggestion['category_id'] : null,
            'was_correct' => $applied ? true : null,
            'model_version' => config('categorization.model_version', 'stub-v1'),
            'created_at' => now(),
        ]);
    }
}
