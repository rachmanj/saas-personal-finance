<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategorizationRule;
use App\Models\User;
use Illuminate\Database\Seeder;

class DefaultFinanceDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (! $user || ! $user->current_team_id) {
            return;
        }

        $teamId = $user->current_team_id;

        $categories = [
            ['Food & Drinks', 'expense', '#FF6B6B'],
            ['Transport', 'expense', '#4ECDC4'],
            ['Utilities', 'expense', '#45B7D1'],
            ['Shopping', 'expense', '#F9CA24'],
            ['Housing', 'expense', '#6C5CE7'],
            ['Entertainment', 'expense', '#A29BFE'],
            ['Health', 'expense', '#FF7675'],
            ['Education', 'expense', '#74B9FF'],
            ['Income', 'income', '#00B894'],
            ['Other', 'expense', '#B2BEC3'],
        ];

        foreach ($categories as [$name, $type, $color]) {
            Category::firstOrCreate(
                ['team_id' => $teamId, 'name' => $name],
                ['type' => $type, 'color' => $color, 'is_active' => true]
            );
        }

        $rules = [
            ['makan', 'Food & Drinks'], ['sarapan', 'Food & Drinks'], ['siang', 'Food & Drinks'],
            ['malam', 'Food & Drinks'], ['minum', 'Food & Drinks'], ['kopi', 'Food & Drinks'],
            ['ngopi', 'Food & Drinks'], ['cemilan', 'Food & Drinks'],
            ['bensin', 'Transport'], ['transport', 'Transport'], ['parkir', 'Transport'],
            ['ojek', 'Transport'], ['gojek', 'Transport'], ['grab', 'Transport'],
            ['listrik', 'Utilities'], ['air', 'Utilities'], ['pulsa', 'Utilities'],
            ['internet', 'Utilities'], ['wifi', 'Utilities'],
            ['belanja', 'Shopping'], ['beli', 'Shopping'],
            ['sewa', 'Housing'], ['kos', 'Housing'],
            ['nonton', 'Entertainment'], ['game', 'Entertainment'],
            ['obat', 'Health'], ['dokter', 'Health'],
            ['kursus', 'Education'], ['buku', 'Education'],
            ['gaji', 'Income'], ['bonus', 'Income'], ['dapat', 'Income'],
            ['transfer', 'Income'], ['jual', 'Income'],
        ];

        foreach ($rules as [$keyword, $catName]) {
            $cat = Category::where('team_id', $teamId)->where('name', $catName)->first();
            if ($cat) {
                CategorizationRule::firstOrCreate(
                    ['team_id' => $teamId, 'pattern' => $keyword],
                    ['user_id' => $user->id, 'category_id' => $cat->id, 'confidence' => 1.0, 'source' => 'manual']
                );
            }
        }
    }
}
