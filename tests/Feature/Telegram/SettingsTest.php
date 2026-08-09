<?php

namespace Tests\Feature\Telegram;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Models\TelegramUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->user = User::factory()->create();
        (new CreatePersonalTeamAction)->execute($this->user);

        $this->actingAs($this->user);
    }

    public function test_get_settings_returns_telegram_user_data_when_linked(): void
    {
        TelegramUser::create([
            'user_id' => $this->user->id,
            'chat_id' => 123456789,
            'username' => 'johndoe',
            'first_name' => 'John',
            'is_active' => true,
            'settings' => [
                'daily_summary' => true,
                'budget_alerts' => true,
                'bill_reminders' => true,
            ],
            'linked_at' => now(),
        ]);

        $response = $this->getJson('/api/telegram/settings');

        $response->assertOk()
            ->assertJsonPath('data.username', 'johndoe')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.settings.daily_summary', true)
            ->assertJsonPath('data.settings.budget_alerts', true)
            ->assertJsonPath('data.settings.bill_reminders', true)
            ->assertJsonPath('data.linked', true);
    }

    public function test_get_settings_returns_unlinked_when_no_telegram_user(): void
    {
        $response = $this->getJson('/api/telegram/settings');

        $response->assertOk()
            ->assertJsonPath('data.linked', false)
            ->assertJsonPath('data.telegram_user', null);
    }

    public function test_update_settings_persists_preferences(): void
    {
        TelegramUser::create([
            'user_id' => $this->user->id,
            'chat_id' => 123456789,
            'username' => 'johndoe',
            'first_name' => 'John',
            'is_active' => true,
            'settings' => ['daily_summary' => true],
            'linked_at' => now(),
        ]);

        $response = $this->putJson('/api/telegram/settings', [
            'daily_summary' => false,
            'budget_alerts' => true,
            'bill_reminders' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.settings.daily_summary', false)
            ->assertJsonPath('data.settings.budget_alerts', true)
            ->assertJsonPath('data.settings.bill_reminders', false);

        $this->assertDatabaseHas('telegram_users', [
            'user_id' => $this->user->id,
        ]);

        $telegramUser = TelegramUser::where('user_id', $this->user->id)->first();
        $this->assertFalse($telegramUser->settings['daily_summary']);
        $this->assertTrue($telegramUser->settings['budget_alerts']);
        $this->assertFalse($telegramUser->settings['bill_reminders']);
    }

    public function test_update_settings_requires_linked_account(): void
    {
        $response = $this->putJson('/api/telegram/settings', [
            'daily_summary' => true,
        ]);

        $response->assertStatus(404);
    }

    public function test_unlink_removes_telegram_user_record(): void
    {
        TelegramUser::create([
            'user_id' => $this->user->id,
            'chat_id' => 123456789,
            'username' => 'johndoe',
            'first_name' => 'John',
            'is_active' => true,
            'settings' => [],
            'linked_at' => now(),
        ]);

        $response = $this->deleteJson('/api/telegram/unlink');

        $response->assertOk()
            ->assertJsonPath('message', 'Telegram account unlinked.');

        $this->assertDatabaseMissing('telegram_users', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_unlink_returns_404_when_not_linked(): void
    {
        $response = $this->deleteJson('/api/telegram/unlink');

        $response->assertNotFound();
    }

    public function test_generate_link_token_creates_one_time_token(): void
    {
        Cache::shouldReceive('put')
            ->once()
            ->withArgs(function ($key, $value, $ttl) {
                return str_starts_with($key, 'telegram_link_token_')
                    && $value === $this->user->id
                    && $ttl === 600;
            });

        $response = $this->postJson('/api/telegram/generate-link-token');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_update_settings_validates_boolean_fields(): void
    {
        TelegramUser::create([
            'user_id' => $this->user->id,
            'chat_id' => 123456789,
            'username' => 'johndoe',
            'first_name' => 'John',
            'is_active' => true,
            'settings' => [],
            'linked_at' => now(),
        ]);

        $response = $this->putJson('/api/telegram/settings', [
            'daily_summary' => 'not-a-bool',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['daily_summary']);
    }
}
