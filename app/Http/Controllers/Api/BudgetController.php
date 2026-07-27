<?php

namespace App\Http\Controllers\Api;

use App\Actions\Budgets\CalculateBudgetUtilizationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBudgetRequest;
use App\Http\Requests\UpdateBudgetRequest;
use App\Models\Budget;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
    public function __construct(private CalculateBudgetUtilizationAction $utilizationAction) {}

    public function index(): JsonResponse
    {
        $budgets = Budget::with('category')
            ->orderByDesc('start_date')
            ->get()
            ->map(fn (Budget $budget) => $this->appendUtilization($budget));

        return response()->json(['data' => $budgets, 'message' => 'Budgets retrieved', 'errors' => null, 'meta' => null]);
    }

    public function store(StoreBudgetRequest $request): JsonResponse
    {
        $budget = Budget::create([
            ...$request->validated(),
            'user_id' => Auth::id(),
        ]);

        $budget->load('category');

        return response()->json([
            'data' => $this->appendUtilization($budget),
            'message' => 'Budget created',
            'errors' => null,
            'meta' => null,
        ], 201);
    }

    public function show(Budget $budget): JsonResponse
    {
        $budget->load('category');

        return response()->json([
            'data' => $this->appendUtilization($budget),
            'message' => 'Budget retrieved',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function update(UpdateBudgetRequest $request, Budget $budget): JsonResponse
    {
        $budget->update($request->validated());
        $budget->load('category');

        return response()->json([
            'data' => $this->appendUtilization($budget),
            'message' => 'Budget updated',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function destroy(Budget $budget): JsonResponse
    {
        $budget->delete();

        return response()->json(null, 204);
    }

    public function alerts(): JsonResponse
    {
        $alerts = Budget::with('category')
            ->get()
            ->map(fn (Budget $budget) => $this->appendUtilization($budget))
            ->filter(fn (array $budget) => in_array($budget['utilization']['status'], ['warning', 'over'], true))
            ->values();

        return response()->json(['data' => $alerts, 'message' => 'Budget alerts retrieved', 'errors' => null, 'meta' => null]);
    }

    private function appendUtilization(Budget $budget): array
    {
        return [
            ...$budget->toArray(),
            'utilization' => $this->utilizationAction->execute($budget),
        ];
    }
}
