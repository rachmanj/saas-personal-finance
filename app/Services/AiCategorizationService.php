<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Auth;

class AiCategorizationService
{
    /**
     * Indonesian keyword → category name mappings.
     *
     * @var array<string, string>
     */
    private const KEYWORD_MAP = [
        'makan' => 'Food',
        'makanan' => 'Food',
        'warung' => 'Food',
        'restoran' => 'Food',
        'kopi' => 'Food',
        'bensin' => 'Transport',
        'pertamax' => 'Transport',
        'gojek' => 'Transport',
        'grab' => 'Transport',
        'ojek' => 'Transport',
        'parkir' => 'Transport',
        'listrik' => 'Utilities',
        'pln' => 'Utilities',
        'air' => 'Utilities',
        'pdam' => 'Utilities',
        'internet' => 'Utilities',
        'wifi' => 'Utilities',
        'pulsa' => 'Utilities',
        'tagihan' => 'Utilities',
    ];

    /**
     * @return array{category_id: int|null, confidence: float}
     */
    public function categorize(string $description, ?string $merchant = null): array
    {
        $haystack = strtolower(trim($description.' '.($merchant ?? '')));

        if ($haystack === '') {
            return ['category_id' => null, 'confidence' => 0.0];
        }

        $teamId = Auth::user()?->current_team_id;

        foreach (self::KEYWORD_MAP as $keyword => $categoryName) {
            if (! str_contains($haystack, $keyword)) {
                continue;
            }

            $category = Category::query()
                ->when($teamId, fn ($query) => $query->where('team_id', $teamId))
                ->where('name', $categoryName)
                ->where('is_active', true)
                ->first();

            if ($category) {
                return [
                    'category_id' => $category->id,
                    'confidence' => 0.800,
                ];
            }
        }

        return ['category_id' => null, 'confidence' => 0.0];
    }
}
