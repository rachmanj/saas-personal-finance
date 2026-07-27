<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBillReminderRequest;
use App\Http\Requests\UpdateBillReminderRequest;
use App\Models\BillReminder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillReminderController extends Controller
{
    public function index(): JsonResponse
    {
        $reminders = BillReminder::orderBy('due_date')->get();

        return response()->json([
            'data' => $reminders,
            'message' => 'Bill reminders retrieved',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function store(StoreBillReminderRequest $request): JsonResponse
    {
        $reminder = BillReminder::create([
            ...$request->validated(),
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'data' => $reminder,
            'message' => 'Bill reminder created',
            'errors' => null,
            'meta' => null,
        ], 201);
    }

    public function show(BillReminder $billReminder): JsonResponse
    {
        return response()->json([
            'data' => $billReminder,
            'message' => 'Bill reminder retrieved',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function update(UpdateBillReminderRequest $request, BillReminder $billReminder): JsonResponse
    {
        $billReminder->update($request->validated());

        return response()->json([
            'data' => $billReminder->fresh(),
            'message' => 'Bill reminder updated',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function destroy(BillReminder $billReminder): JsonResponse
    {
        $billReminder->delete();

        return response()->json(null, 204);
    }

    public function paid(BillReminder $billReminder): JsonResponse
    {
        $billReminder->update([
            'is_paid' => true,
            'paid_at' => now(),
        ]);

        return response()->json([
            'data' => $billReminder->fresh(),
            'message' => 'Bill reminder marked as paid',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function dueSoon(): JsonResponse
    {
        $reminders = BillReminder::where('is_paid', false)
            ->where('due_date', '<=', now()->addDays(7))
            ->where('due_date', '>=', today())
            ->orderBy('due_date')
            ->get();

        return response()->json([
            'data' => $reminders,
            'message' => 'Due soon bill reminders retrieved',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => ['required', 'string'],
            'key' => ['required', 'string'],
            'token' => ['required', 'string'],
        ]);

        $request->user()->updatePushSubscription(
            $request->endpoint,
            $request->key,
            $request->token,
        );

        return response()->json([
            'data' => null,
            'message' => 'Push subscription saved',
            'errors' => null,
            'meta' => null,
        ]);
    }
}
