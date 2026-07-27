<?php

namespace App\Services;

use App\Models\CategorizationRule;
use Illuminate\Support\Facades\Auth;

class CategorizationRuleService
{
    public function __construct(private AiCategorizationService $aiService) {}

    /**
     * @return array{category_id: int|null, confidence: float, source: string}
     */
    public function suggest(string $description, ?string $merchant = null): array
    {
        $haystack = strtolower(trim($description.' '.($merchant ?? '')));

        if ($haystack === '') {
            return ['category_id' => null, 'confidence' => 0.0, 'source' => 'none'];
        }

        $rules = CategorizationRule::query()
            ->orderByDesc('confidence')
            ->get();

        foreach ($rules as $rule) {
            if ($this->matchesPattern($haystack, $rule->pattern)) {
                return [
                    'category_id' => $rule->category_id,
                    'confidence' => (float) $rule->confidence,
                    'source' => 'rule',
                ];
            }
        }

        $aiResult = $this->aiService->categorize($description, $merchant);

        if ($aiResult['category_id'] === null) {
            return ['category_id' => null, 'confidence' => 0.0, 'source' => 'none'];
        }

        return [
            'category_id' => $aiResult['category_id'],
            'confidence' => $aiResult['confidence'],
            'source' => 'ai',
        ];
    }

    private function matchesPattern(string $haystack, string $pattern): bool
    {
        if ($this->isRegexPattern($pattern)) {
            return (bool) @preg_match($pattern, $haystack);
        }

        return str_contains($haystack, strtolower($pattern));
    }

    private function isRegexPattern(string $pattern): bool
    {
        return str_starts_with($pattern, '/') && str_ends_with($pattern, '/');
    }
}
