<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionSuggestionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        $query = $request->input('query');

        // Merchant autocomplete — distinct descriptions from last 90 days
        $merchants = Transaction::query()
            ->whereNotNull('description')
            ->where('description', 'like', "%{$query}%")
            ->where('transaction_date', '>=', now()->subDays(90)->toDateString())
            ->select('description')
            ->distinct()
            ->limit(10)
            ->pluck('description')
            ->values();

        // Category prediction — simple keyword match (stub for AI in Phase 10)
        $predictedCategoryId = null;
        $predictedCategoryName = null;

        // Stub: check if any existing transaction with similar description has a category
        $existing = Transaction::query()
            ->whereNotNull('category_id')
            ->where('description', 'like', "%{$query}%")
            ->with('category')
            ->first();

        if ($existing && $existing->category) {
            $predictedCategoryId = $existing->category_id;
            $predictedCategoryName = $existing->category->name;
        }

        return response()->json([
            'data' => [
                'merchants' => $merchants,
                'predicted_category_id' => $predictedCategoryId,
                'predicted_category_name' => $predictedCategoryName,
            ],
            'message' => 'Suggestions retrieved',
            'errors' => null,
            'meta' => null,
        ]);
    }
}
