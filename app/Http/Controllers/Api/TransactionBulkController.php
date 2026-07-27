<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionBulkController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'action' => ['required', 'in:delete,categorize,update'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'exists:transactions,id'],
            'payload' => ['nullable', 'array'],
        ]);

        $ids = $request->input('ids');
        $action = $request->input('action');
        $payload = $request->input('payload', []);

        $transactions = Transaction::whereIn('id', $ids)->get();
        $count = $transactions->count();

        match ($action) {
            'delete' => Transaction::whereIn('id', $ids)->delete(),
            'categorize' => Transaction::whereIn('id', $ids)->update([
                'category_id' => $payload['category_id'] ?? null,
            ]),
            'update' => Transaction::whereIn('id', $ids)->update(array_intersect_key(
                $payload,
                array_flip(['is_reconciled', 'category_id', 'description'])
            )),
        };

        return response()->json([
            'data' => ['affected' => $count],
            'message' => "Bulk {$action} completed",
            'errors' => null,
            'meta' => null,
        ]);
    }
}
