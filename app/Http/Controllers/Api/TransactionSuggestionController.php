<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\CategorizationRuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionSuggestionController extends Controller
{
    public function __invoke(Request $request, CategorizationRuleService $ruleService): JsonResponse
    {
        $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        $query = $request->input('query');

        $merchants = Transaction::query()
            ->whereNotNull('description')
            ->where('description', 'like', "%{$query}%")
            ->where('transaction_date', '>=', now()->subDays(90)->toDateString())
            ->select('description')
            ->distinct()
            ->limit(10)
            ->pluck('description')
            ->values();

        $suggestion = $ruleService->suggest($query);

        $predictedCategoryId = $suggestion['category_id'];
        $predictedCategoryName = null;

        if ($predictedCategoryId !== null) {
            $predictedCategoryName = Category::query()->find($predictedCategoryId)?->name;
        }

        return response()->json([
            'data' => [
                'merchants' => $merchants,
                'predicted_category_id' => $predictedCategoryId,
                'predicted_category_name' => $predictedCategoryName,
                'confidence' => $suggestion['confidence'],
                'source' => $suggestion['source'],
            ],
            'message' => 'Suggestions retrieved',
            'errors' => null,
            'meta' => null,
        ]);
    }
}
