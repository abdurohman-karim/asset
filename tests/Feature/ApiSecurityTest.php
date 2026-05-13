<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.telegram.webhook_secret', 'test-secret');
    }

    public function test_rpc_requires_authentication(): void
    {
        $response = $this->postJson('/api/rpc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'goal.list',
            'params' => [],
        ]);

        $response->assertUnauthorized();
    }

    public function test_authenticated_rpc_uses_authenticated_user_only(): void
    {
        $authUser = User::factory()->create(['tg_user_id' => '1001']);
        $otherUser = User::factory()->create(['tg_user_id' => '2002']);

        $authUser->goals()->create([
            'title' => 'Auth Goal',
            'amount_total' => 1000,
            'amount_saved' => 100,
            'priority' => 1,
            'status' => 'active',
        ]);

        $otherUser->goals()->create([
            'title' => 'Other Goal',
            'amount_total' => 2000,
            'amount_saved' => 200,
            'priority' => 1,
            'status' => 'active',
        ]);

        Sanctum::actingAs($authUser);

        $response = $this->postJson('/api/rpc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'goal.list',
            'params' => [
                'user_id' => $otherUser->id,
                'tg_user_id' => $otherUser->tg_user_id,
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('result.goals.0.title', 'Auth Goal')
            ->assertJsonMissing(['title' => 'Other Goal']);
    }

    public function test_telegram_secret_allows_rpc_for_matching_tg_user(): void
    {
        $user = User::factory()->create(['tg_user_id' => '1001']);
        $user->goals()->create([
            'title' => 'Bot Goal',
            'amount_total' => 1000,
            'amount_saved' => 100,
            'priority' => 1,
            'status' => 'active',
        ]);

        $response = $this->withHeader('X-Telegram-Bot-Secret', 'test-secret')
            ->postJson('/api/rpc', [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'goal.list',
                'params' => [
                    'tg_user_id' => '1001',
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('result.goals.0.title', 'Bot Goal');
    }

    public function test_switch_theme_ignores_spoofed_user_id_and_updates_authenticated_user(): void
    {
        $authUser = User::factory()->create([
            'settings' => ['theme' => 'light'],
        ]);
        $otherUser = User::factory()->create([
            'settings' => ['theme' => 'light'],
        ]);

        Sanctum::actingAs($authUser);

        $response = $this->postJson('/api/switch-theme', [
            'user_id' => $otherUser->id,
        ]);

        $response->assertOk()->assertSee('dark');
        $this->assertSame('dark', $authUser->fresh()->settings['theme']);
        $this->assertSame('light', $otherUser->fresh()->settings['theme']);
    }

    public function test_telegram_endpoint_requires_secret(): void
    {
        $response = $this->postJson('/api/telegram/status', [
            'tg_user_id' => '123456',
        ]);

        $response->assertUnauthorized();
    }

    public function test_telegram_endpoint_accepts_valid_secret(): void
    {
        $response = $this->withHeader('X-Telegram-Bot-Secret', 'test-secret')
            ->postJson('/api/telegram/status', [
                'tg_user_id' => '123456',
            ]);

        $response->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('registered', false);
    }
}
