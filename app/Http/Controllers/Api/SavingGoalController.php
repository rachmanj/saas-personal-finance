<?php

namespace App\Http\Controllers\Api;

use App\Actions\Goals\AddGoalContributionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSavingGoalRequest;
use App\Http\Requests\UpdateSavingGoalRequest;
use App\Models\SavingGoal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavingGoalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $goals = SavingGoal::with('contributions')
            ->latest()
            ->get();

        return response()->json([
            'data' => $goals,
            'message' => 'Saving goals retrieved successfully.',
            'errors' => null,
            'meta' => null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSavingGoalRequest $request): JsonResponse
    {
        $goal = SavingGoal::create($request->validated());

        return response()->json([
            'data' => $goal,
            'message' => 'Saving goal created successfully.',
            'errors' => null,
            'meta' => null,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(SavingGoal $savingGoal): JsonResponse
    {
        $savingGoal->load('contributions');

        return response()->json([
            'data' => $savingGoal,
            'message' => 'Saving goal retrieved successfully.',
            'errors' => null,
            'meta' => null,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSavingGoalRequest $request, SavingGoal $savingGoal): JsonResponse
    {
        $savingGoal->update($request->validated());

        return response()->json([
            'data' => $savingGoal->fresh(),
            'message' => 'Saving goal updated successfully.',
            'errors' => null,
            'meta' => null,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SavingGoal $savingGoal): JsonResponse
    {
        $savingGoal->delete();

        return response()->json(null, 204);
    }

    /**
     * Add a contribution to a saving goal.
     */
    public function addContribution(
        Request $request,
        SavingGoal $savingGoal,
        AddGoalContributionAction $action
    ): JsonResponse {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'contributed_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $action->execute($savingGoal, $validated);

        return response()->json([
            'data' => $result,
            'message' => 'Contribution added successfully.',
            'errors' => null,
            'meta' => null,
        ]);
    }
}