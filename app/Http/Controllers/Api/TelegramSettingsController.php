<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TelegramUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TelegramSettingsController extends Controller
{
    /**
     * Get the current user's Telegram settings.
     */
    public function show(): JsonResponse
    {
        $telegramUser = TelegramUser::where('user_id', auth()->id())->first();

        if (!$telegramUser) {
            return response()->json([
                'data' => [
                    'linked' => false,
                    'telegram_user' => null,
                ],
                'message' => 'Telegram not linked.',
                'errors' => null,
                'meta' => null,
            ]);
        }

        return response()->json([
            'data' => [
                'linked' => true,
                'id' => $telegramUser->id,
                'username' => $telegramUser->username,
                'first_name' => $telegramUser->first_name,
                'last_name' => $telegramUser->last_name,
                'is_active' => $telegramUser->is_active,
                'settings' => $telegramUser->settings ?? [],
                'linked_at' => $telegramUser->linked_at?->toISOString(),
            ],
            'message' => 'Telegram settings retrieved.',
            'errors' => null,
            'meta' => null,
        ]);
    }

    /**
     * Update Telegram notification preferences.
     */
    public function update(Request $request): JsonResponse
    {
        $telegramUser = TelegramUser::where('user_id', auth()->id())->first();

        if (!$telegramUser) {
            return response()->json([
                'data' => null,
                'message' => 'Telegram account not linked.',
                'errors' => null,
                'meta' => null,
            ], 404);
        }

        $validated = $request->validate([
            'daily_summary' => ['sometimes', 'boolean'],
            'budget_alerts' => ['sometimes', 'boolean'],
            'bill_reminders' => ['sometimes', 'boolean'],
        ]);

        $settings = $telegramUser->settings ?? [];
        foreach ($validated as $key => $value) {
            $settings[$key] = $value;
        }

        $telegramUser->update(['settings' => $settings]);

        return response()->json([
            'data' => [
                'settings' => $telegramUser->settings,
            ],
            'message' => 'Settings updated.',
            'errors' => null,
            'meta' => null,
        ]);
    }

    /**
     * Unlink (remove) the Telegram user record.
     */
    public function unlink(): JsonResponse
    {
        $telegramUser = TelegramUser::where('user_id', auth()->id())->first();

        if (!$telegramUser) {
            return response()->json([
                'data' => null,
                'message' => 'Telegram account not linked.',
                'errors' => null,
                'meta' => null,
            ], 404);
        }

        $telegramUser->delete();

        return response()->json([
            'data' => null,
            'message' => 'Telegram account unlinked.',
            'errors' => null,
            'meta' => null,
        ]);
    }

    /**
     * Generate a one-time link token for the /link Telegram bot command.
     */
    public function generateLinkToken(): JsonResponse
    {
        $token = Str::random(64);
        $key = 'telegram_link_token_' . $token;

        Cache::put($key, auth()->id(), 600); // 10 minutes

        return response()->json([
            'data' => [
                'token' => $token,
            ],
            'message' => 'Link token generated.',
            'errors' => null,
            'meta' => null,
        ]);
    }
}
