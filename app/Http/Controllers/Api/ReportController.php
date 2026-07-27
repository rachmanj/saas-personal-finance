<?php

namespace App\Http\Controllers\Api;

use App\Actions\Reports\IncomeVsExpenseAction;
use App\Actions\Reports\MonthlySummaryAction;
use App\Actions\Reports\NetWorthAction;
use App\Actions\Reports\SpendingByCategoryAction;
use App\Actions\Reports\TrendAction;
use App\Actions\Reports\YearOverYearAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function spendingByCategory(Request $request, SpendingByCategoryAction $action): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $data = $action->execute(
            Auth::user()->current_team_id,
            $validated['start_date'],
            $validated['end_date']
        );

        return response()->json([
            'data' => $data,
            'message' => 'Spending by category report',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function incomeVsExpense(Request $request, IncomeVsExpenseAction $action): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $data = $action->execute(
            Auth::user()->current_team_id,
            $validated['start_date'],
            $validated['end_date']
        );

        return response()->json([
            'data' => $data,
            'message' => 'Income vs expense report',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function monthlySummary(Request $request, MonthlySummaryAction $action): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $data = $action->execute(
            Auth::user()->current_team_id,
            (int) $validated['year']
        );

        return response()->json([
            'data' => $data,
            'message' => 'Monthly summary report',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function trend(Request $request, TrendAction $action): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $data = $action->execute(
            Auth::user()->current_team_id,
            $validated['start_date'],
            $validated['end_date']
        );

        return response()->json([
            'data' => $data,
            'message' => 'Trend report',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function yearOverYear(Request $request, YearOverYearAction $action): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $data = $action->execute(
            Auth::user()->current_team_id,
            (int) $validated['year']
        );

        return response()->json([
            'data' => $data,
            'message' => 'Year over year report',
            'errors' => null,
            'meta' => null,
        ]);
    }

    public function netWorth(NetWorthAction $action): JsonResponse
    {
        $data = $action->execute(Auth::user()->current_team_id);

        return response()->json([
            'data' => $data,
            'message' => 'Net worth report',
            'errors' => null,
            'meta' => null,
        ]);
    }
}