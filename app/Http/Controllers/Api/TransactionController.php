<?php

namespace App\Http\Controllers\Api;

use App\Actions\Transactions\CreateTransactionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::query()
            ->with(['account', 'category', 'toAccount', 'tags']);

        // Filter by account
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->input('account_id'));
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('transaction_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('transaction_date', '<=', $request->input('date_to'));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->input('sorter', 'transaction_date');
        $sortOrder = $request->input('sortOrder', 'descend');
        $direction = $sortOrder === 'ascend' ? 'asc' : 'desc';

        $allowedSortFields = ['transaction_date', 'amount', 'description', 'created_at'];
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $direction);
        } else {
            $query->orderBy('transaction_date', 'desc');
        }

        // ProTable-compatible pagination
        $pageSize = (int) $request->input('pageSize', 20);
        $pageSize = min(max($pageSize, 1), 100);

        $paginator = $query->paginate($pageSize);

        return response()->json([
            'data' => $paginator->items(),
            'message' => 'Transactions retrieved',
            'errors' => null,
            'meta' => [
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(StoreTransactionRequest $request, CreateTransactionAction $action): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $transaction = $action->execute($data);

        return response()->json([
            'data' => $transaction,
            'message' => 'Transaction created',
            'errors' => null,
            'meta' => null,
        ], 201);
    }

    public function show(Transaction $transaction): JsonResponse
    {
        $transaction->load(['account', 'category', 'toAccount', 'splits', 'tags']);

        return response()->json([
            'data' => $transaction,
            'message' => 'Transaction retrieved',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction): JsonResponse
    {
        $transaction->update($request->validated());

        return response()->json([
            'data' => $transaction->fresh(['account', 'category', 'toAccount', 'splits', 'tags']),
            'message' => 'Transaction updated',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function destroy(Transaction $transaction): JsonResponse
    {
        $transaction->delete();

        return response()->json(null, 204);
    }
}
