<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithCurrencies;
use Tests\TestCase;

class CurrencyRpcTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithCurrencies;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.telegram.webhook_secret', 'test-secret');
        $this->createCurrenciesTable();
        $this->seedCurrencies();
    }

    public function test_authenticated_user_can_list_active_currencies(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/rpc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'currency.list',
            'params' => [],
        ]);

        $response->assertOk()
            ->assertJsonPath('result.success', true)
            ->assertJsonCount(2, 'result.data')
            ->assertJsonMissing(['code' => 'RUB']);
    }

    public function test_user_can_set_active_currency(): void
    {
        $user = User::factory()->create(['tg_user_id' => '1001']);

        $response = $this->withHeader('X-Telegram-Bot-Secret', 'test-secret')
            ->postJson('/api/rpc', [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'currency.set',
                'params' => [
                    'tg_user_id' => '1001',
                    'currency_code' => 'USD',
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('result.success', true)
            ->assertJsonPath('result.data.code', 'USD');

        $this->assertSame('USD', $user->fresh()->settings['currency_code']);
    }

    public function test_user_cannot_set_inactive_currency(): void
    {
        $user = User::factory()->create(['tg_user_id' => '1001']);

        $response = $this->withHeader('X-Telegram-Bot-Secret', 'test-secret')
            ->postJson('/api/rpc', [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'currency.set',
                'params' => [
                    'tg_user_id' => '1001',
                    'currency_code' => 'RUB',
                ],
            ]);

        $response->assertStatus(500)
            ->assertJsonPath('error.message', 'Currency not found or inactive');

        $this->assertArrayNotHasKey('currency_code', $user->fresh()->settings ?? []);
    }

    public function test_authenticated_currency_set_ignores_spoofed_identifiers(): void
    {
        $authUser = User::factory()->create([
            'tg_user_id' => '1001',
            'settings' => ['currency_code' => 'UZS'],
        ]);
        $otherUser = User::factory()->create([
            'tg_user_id' => '2002',
            'settings' => ['currency_code' => 'UZS'],
        ]);

        Sanctum::actingAs($authUser);

        $response = $this->postJson('/api/rpc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'currency.set',
            'params' => [
                'user_id' => $otherUser->id,
                'tg_user_id' => $otherUser->tg_user_id,
                'currency_code' => 'USD',
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('result.data.code', 'USD');

        $this->assertSame('USD', $authUser->fresh()->settings['currency_code']);
        $this->assertSame('UZS', $otherUser->fresh()->settings['currency_code']);
    }
}
