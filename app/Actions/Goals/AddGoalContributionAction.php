<?php

namespace App\Actions\Goals;

use App\Models\SavingGoal;
use Illuminate\Support\Facades\DB;

class AddGoalContributionAction
{
    public function execute(SavingGoal $goal, array $data): array
    {
        return DB::transaction(function () use ($goal, $data) {
            $contribution = $goal->contributions()->create([
                'amount' => $data['amount'],
                'contributed_at' => $data['contributed_at'],
                'note' => $data['note'] ?? null,
            ]);

            $goal->increment('current_amount', $data['amount']);
            $goal->refresh();

            // Mark as completed if target reached
            if ($goal->current_amount >= $goal->target_amount && ! $goal->is_completed) {
                $goal->update([
                    'is_completed' => true,
                    'completed_at' => now(),
                ]);
            }

            return [
                'goal' => $goal->fresh()->load('contributions'),
                'contribution' => $contribution,
            ];
        });
    }
}