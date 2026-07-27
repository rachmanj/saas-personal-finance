<?php

namespace App\Http\Controllers\Api;

use App\Actions\Dashboard\BuildDashboardSummaryAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function summary(BuildDashboardSummaryAction $action): JsonResponse
    {
        $teamId = Auth::user()->current_team_id;

        $data = $action->execute($teamId);

        return response()->json([
            'data' => $data,
            'message' => 'Dashboard summary retrieved',
            'errors' => null,
            'meta' => null,
        ]);
    }
}