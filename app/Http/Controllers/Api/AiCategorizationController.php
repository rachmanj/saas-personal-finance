<?php

namespace App\Http\Controllers\Api;

use App\Enums\CategorizationRuleSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\CategorizeTransactionRequest;
use App\Http\Requests\StoreCategorizationRuleRequest;
use App\Http\Requests\UpdateCategorizationRuleRequest;
use App\Jobs\AutoCategorizeTransaction;
use App\Jobs\BatchAutoCategorize;
use App\Models\AiCategorizationLog;
use App\Models\CategorizationRule;
use App\Models\Transaction;
use App\Services\CategorizationRuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AiCategorizationController extends Controller
{
    public function __construct(private CategorizationRuleService $ruleService) {}

    public function categorize(CategorizeTransactionRequest $request): JsonResponse
    {
        $transaction = Transaction::findOrFail($request->validated('transaction_id'));

        $suggestion = $this->ruleService->suggest($transaction->description ?? '');

        $minConfidence = config('categorization.min_confidence', 0.7);
        $applied = $suggestion['category_id'] !== null
            && $suggestion['confidence'] >= $minConfidence;

        if ($applied) {
            $transaction->update(['category_id' => $suggestion['category_id']]);
        }

        AiCategorizationLog::create([
            'transaction_id' => $transaction->id,
            'predicted_category_id' => $suggestion['category_id'],
            'confidence' => $suggestion['confidence'],
            'actual_category_id' => $applied ? $suggestion['category_id'] : null,
            'was_correct' => $applied ? true : null,
            'model_version' => config('categorization.model_version', 'stub-v1'),
            'created_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'transaction_id' => $transaction->id,
                'predicted_category_id' => $suggestion['category_id'],
                'confidence' => $suggestion['confidence'],
                'source' => $suggestion['source'],
                'applied' => $applied,
            ],
            'message' => 'Transaction categorized',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function batchCategorize(): JsonResponse
    {
        $teamId = Auth::user()->current_team_id;

        $count = Transaction::whereNull('category_id')->count();

        BatchAutoCategorize::dispatch($teamId);

        return response()->json([
            'data' => ['dispatched' => $count],
            'message' => 'Batch categorization queued',
            'errors' => null,
            'meta' => null,
        ], 202);
    }

    public function indexRules(): JsonResponse
    {
        $rules = CategorizationRule::with('category')
            ->orderByDesc('confidence')
            ->get();

        return response()->json([
            'data' => $rules,
            'message' => 'Categorization rules retrieved',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function storeRule(StoreCategorizationRuleRequest $request): JsonResponse
    {
        $rule = CategorizationRule::create([
            ...$request->validated(),
            'user_id' => Auth::id(),
            'confidence' => $request->input('confidence', 1.0),
            'source' => CategorizationRuleSource::Manual,
        ]);

        $rule->load('category');

        return response()->json([
            'data' => $rule,
            'message' => 'Categorization rule created',
            'errors' => null,
            'meta' => null,
        ], 201);
    }

    public function updateRule(UpdateCategorizationRuleRequest $request, CategorizationRule $categorizationRule): JsonResponse
    {
        $categorizationRule->update($request->validated());
        $categorizationRule->load('category');

        return response()->json([
            'data' => $categorizationRule,
            'message' => 'Categorization rule updated',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function destroyRule(CategorizationRule $categorizationRule): JsonResponse
    {
        $categorizationRule->delete();

        return response()->json(null, 204);
    }

    public function accuracy(): JsonResponse
    {
        $logs = AiCategorizationLog::query()
            ->whereHas('transaction')
            ->whereNotNull('was_correct')
            ->get();

        $total = $logs->count();
        $correct = $logs->where('was_correct', true)->count();

        return response()->json([
            'data' => [
                'total_predictions' => $total,
                'correct_predictions' => $correct,
                'accuracy_rate' => $total > 0 ? round($correct / $total, 4) : 0.0,
            ],
            'message' => 'Categorization accuracy retrieved',
            'errors' => null,
            'meta' => null,
        ]);
    }
}
