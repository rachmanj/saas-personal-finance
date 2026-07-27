<?php

namespace App\Http\Controllers\Api;

use App\Actions\Recurring\CalculateNextDueDateAction;
use App\Actions\Recurring\PostRecurringTransactionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRecurringTransactionRequest;
use App\Http\Requests\UpdateRecurringTransactionRequest;
use App\Models\RecurringTransaction;
use App\Models\RecurringTransactionLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class RecurringTransactionController extends Controller
{
    public function __construct(
        private CalculateNextDueDateAction $calculateNextDueDateAction,
        private PostRecurringTransactionAction $postRecurringTransactionAction,
    ) {}

    public function index(): JsonResponse
    {
        $recurring = RecurringTransaction::with(['category', 'account'])
            ->orderBy('next_due_date')
            ->get();

        return response()->json(['data' => $recurring, 'message' => 'Recurring transactions retrieved', 'errors' => null, 'meta' => null]);
    }

    public function store(StoreRecurringTransactionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $startDate = Carbon::parse($validated['start_date']);
        $frequency = $validated['frequency'];
        $interval = $validated['interval'] ?? 1;

        $recurring = RecurringTransaction::create([
            ...$validated,
            'interval' => $interval,
            'user_id' => Auth::id(),
            'next_due_date' => $this->calculateNextDueDateAction->execute($frequency, $interval, $startDate),
            'template_type' => $validated['template_type'] ?? 'custom',
            'description' => $validated['description'] ?? '',
        ]);

        $recurring->load(['category', 'account']);

        return response()->json([
            'data' => $recurring,
            'message' => 'Recurring transaction created',
            'errors' => null,
            'meta' => null,
        ], 201);
    }

    public function show(RecurringTransaction $recurringTransaction): JsonResponse
    {
        $recurringTransaction->load(['category', 'account', 'logs']);

        return response()->json([
            'data' => $recurringTransaction,
            'message' => 'Recurring transaction retrieved',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function update(UpdateRecurringTransactionRequest $request, RecurringTransaction $recurringTransaction): JsonResponse
    {
        $recurringTransaction->update($request->validated());
        $recurringTransaction->load(['category', 'account']);

        return response()->json([
            'data' => $recurringTransaction,
            'message' => 'Recurring transaction updated',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function destroy(RecurringTransaction $recurringTransaction): JsonResponse
    {
        $recurringTransaction->delete();

        return response()->json(null, 204);
    }

    public function skip(RecurringTransaction $recurringTransaction): JsonResponse
    {
        RecurringTransactionLog::create([
            'recurring_transaction_id' => $recurringTransaction->id,
            'was_skipped' => true,
        ]);

        $recurringTransaction->update([
            'next_due_date' => $this->calculateNextDueDateAction->execute(
                $recurringTransaction->frequency,
                $recurringTransaction->interval,
                $recurringTransaction->next_due_date,
            ),
        ]);

        $recurringTransaction->load(['category', 'account']);

        return response()->json([
            'data' => $recurringTransaction,
            'message' => 'Recurring transaction skipped',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function postNow(RecurringTransaction $recurringTransaction): JsonResponse
    {
        $this->postRecurringTransactionAction->execute($recurringTransaction);
        $recurringTransaction->refresh()->load(['category', 'account', 'logs']);

        return response()->json([
            'data' => $recurringTransaction,
            'message' => 'Recurring transaction posted',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function upcoming(): JsonResponse
    {
        $upcoming = RecurringTransaction::where('is_active', true)
            ->where('next_due_date', '<=', now()->addDays(30))
            ->where('next_due_date', '>=', today())
            ->orderBy('next_due_date')
            ->get();

        return response()->json(['data' => $upcoming, 'message' => 'Upcoming recurring transactions retrieved', 'errors' => null, 'meta' => null]);
    }
}
