<?php

namespace App\Jobs;

use App\Enums\CategorizationRuleSource;
use App\Models\AiCategorizationLog;
use App\Models\CategorizationRule;
use App\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class TrainCategorizationModel implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $teamId) {}

    public function handle(): void
    {
        $logs = AiCategorizationLog::query()
            ->where('was_correct', false)
            ->whereNotNull('actual_category_id')
            ->whereHas('transaction', fn ($query) => $query
                ->withoutGlobalScopes()
                ->where('team_id', $this->teamId))
            ->with(['transaction' => fn ($query) => $query->withoutGlobalScopes()])
            ->get();

        $patterns = [];

        foreach ($logs as $log) {
            $description = $log->transaction?->description;

            if (! $description) {
                continue;
            }

            $keyword = $this->extractKeyword($description);

            if ($keyword === null) {
                continue;
            }

            $key = $keyword.'|'.$log->actual_category_id;

            if (! isset($patterns[$key])) {
                $patterns[$key] = [
                    'pattern' => $keyword,
                    'category_id' => $log->actual_category_id,
                    'count' => 0,
                ];
            }

            $patterns[$key]['count']++;
        }

        foreach ($patterns as $pattern) {
            if ($pattern['count'] < 1) {
                continue;
            }

            $exists = CategorizationRule::withoutGlobalScopes()
                ->where('team_id', $this->teamId)
                ->where('pattern', $pattern['pattern'])
                ->where('category_id', $pattern['category_id'])
                ->exists();

            if ($exists) {
                continue;
            }

            $userId = Transaction::withoutGlobalScopes()
                ->where('team_id', $this->teamId)
                ->value('user_id');

            CategorizationRule::withoutGlobalScopes()->create([
                'team_id' => $this->teamId,
                'user_id' => $userId,
                'pattern' => $pattern['pattern'],
                'category_id' => $pattern['category_id'],
                'confidence' => min(0.5 + ($pattern['count'] * 0.1), 0.95),
                'source' => CategorizationRuleSource::AiTrained,
            ]);
        }
    }

    private function extractKeyword(string $description): ?string
    {
        $words = preg_split('/\s+/', strtolower(trim($description))) ?: [];

        foreach ($words as $word) {
            $word = Str::of($word)->trim('.,!?')->toString();

            if (strlen($word) >= 4) {
                return $word;
            }
        }

        return $words[0] ?? null;
    }
}
