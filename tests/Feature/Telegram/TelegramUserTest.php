<?php

namespace Tests\Feature\Telegram;

use App\Actions\Teams\CreatePersonalTeamAction;
use App\Models\TelegramUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramUserTest extends TestCase
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

    public function test_user_can_link_telegram_account(): void
    {
        $telegramUser = TelegramUser::create([
            'user_id' => $this->user->id,
            'chat_id' => 123456789,
            'username' => 'johndoe',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'is_active' => true,
            'settings' => ['bill_reminders_enabled' => true],
            'linked_at' => now(),
        ]);

        $this->assertDatabaseHas('telegram_users', [
            'user_id' => $this->user->id,
            'chat_id' => 123456789,
            'username' => 'johndoe',
        ]);

        $this->assertTrue($telegramUser->is_active);
        $this->assertNotNull($telegramUser->linked_at);

        // Verify relationship
        $this->assertInstanceOf(User::class, $telegramUser->user);
        $this->assertEquals($this->user->id, $telegramUser->user->id);
    }

    public function test_telegram_users_table_stores_correct_data(): void
    {
        $telegramUser = TelegramUser::create([
            'user_id' => $this->user->id,
            'chat_id' => 987654321,
            'username' => 'janedoe',
            'first_name' => 'Jane',
            'last_name' => null,
            'is_active' => false,
            'settings' => [
                'default_account_id' => 5,
                'bill_reminders_enabled' => false,
                'daily_summary_enabled' => true,
            ],
            'linked_at' => '2026-08-01 10:00:00',
        ]);

        $this->assertDatabaseHas('telegram_users', [
            'id' => $telegramUser->id,
            'user_id' => $this->user->id,
            'chat_id' => 987654321,
            'username' => 'janedoe',
            'first_name' => 'Jane',
            'is_active' => false,
        ]);

        // Verify casts
        $fresh = $telegramUser->fresh();

        $this->assertIsBool($fresh->is_active);
        $this->assertFalse($fresh->is_active);

        $this->assertIsArray($fresh->settings);
        $this->assertEquals(5, $fresh->settings['default_account_id']);
        $this->assertTrue($fresh->settings['daily_summary_enabled']);

        $this->assertNotNull($fresh->linked_at);
        $this->assertEquals('2026-08-01 10:00:00', $fresh->linked_at->format('Y-m-d H:i:s'));
    }

    public function test_user_has_one_telegram_user_relationship(): void
    {
        TelegramUser::create([
            'user_id' => $this->user->id,
            'chat_id' => 555555555,
            'username' => 'testuser',
            'first_name' => 'Test',
            'is_active' => true,
            'linked_at' => now(),
        ]);

        $this->assertInstanceOf(TelegramUser::class, $this->user->telegramUser);
        $this->assertEquals(555555555, $this->user->telegramUser->chat_id);
    }

    public function test_telegram_user_chat_id_is_unique(): void
    {
        TelegramUser::create([
            'user_id' => $this->user->id,
            'chat_id' => 111111111,
            'username' => 'user1',
            'first_name' => 'User',
            'is_active' => true,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        TelegramUser::create([
            'user_id' => User::factory()->create()->id,
            'chat_id' => 111111111,
            'username' => 'user2',
            'first_name' => 'User',
            'is_active' => true,
        ]);
    }
}
