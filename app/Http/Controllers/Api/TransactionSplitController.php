<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionSplit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionSplitController extends Controller
{
    public function store(Request $request, Transaction $transaction): JsonResponse
    {
        $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $split = $transaction->splits()->create($request->only(['category_id', 'amount', 'description']));

        return response()->json([
            'data' => $split,
            'message' => 'Split created',
            'errors' => null,
            'meta' => null,
        ], 201);
    }

    public function destroy(Transaction $transaction, TransactionSplit $split): JsonResponse
    {
        $split->delete();

        return response()->json(null, 204);
    }
}
