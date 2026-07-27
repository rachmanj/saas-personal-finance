<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(): JsonResponse
    {
        $accounts = Account::orderBy('name')->get();

        return response()->json(['data' => $accounts, 'message' => 'Accounts retrieved', 'errors' => null, 'meta' => null]);
    }

    public function store(StoreAccountRequest $request): JsonResponse
    {
        $account = Account::create($request->validated());

        return response()->json(['data' => $account, 'message' => 'Account created', 'errors' => null, 'meta' => null], 201);
    }

    public function show(Account $account): JsonResponse
    {
        return response()->json(['data' => $account, 'message' => 'Account retrieved', 'errors' => null, 'meta' => null]);
    }

    public function update(UpdateAccountRequest $request, Account $account): JsonResponse
    {
        $account->update($request->validated());

        return response()->json(['data' => $account, 'message' => 'Account updated', 'errors' => null, 'meta' => null]);
    }

    public function destroy(Account $account): JsonResponse
    {
        $account->delete();

        return response()->json(null, 204);
    }

    public function reconcile(Request $request, Account $account): JsonResponse
    {
        $request->validate(['balance' => 'required|numeric|min:0']);

        $oldBalance = $account->balance;
        $account->update(['balance' => $request->balance]);

        return response()->json([
            'data' => $account,
            'message' => 'Account reconciled',
            'errors' => null,
            'meta' => ['old_balance' => $oldBalance, 'new_balance' => $account->balance],
        ]);
    }
}
