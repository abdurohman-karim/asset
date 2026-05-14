<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithCurrencies;
use Tests\TestCase;

class CurrencyServiceTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithCurrencies;

    public function test_default_currency_falls_back_to_uzs_without_currency_table(): void
    {
        $currency = app(CurrencyService::class)->defaultCurrency();

        $this->assertSame('UZS', $currency['code']);
        $this->assertSame('сум', $currency['symbol']);
    }

    public function test_preferred_currency_uses_active_selected_currency(): void
    {
        $this->createCurrenciesTable();
        $this->seedCurrencies();

        $user = User::factory()->create([
            'settings' => [
                'currency_code' => 'USD',
            ],
        ]);

        $currency = app(CurrencyService::class)->preferredCurrency($user);

        $this->assertSame('USD', $currency['code']);
        $this->assertSame('$', $currency['symbol']);
    }

    public function test_inactive_selected_currency_falls_back_to_default(): void
    {
        $this->createCurrenciesTable();
        $this->seedCurrencies();

        $user = User::factory()->create([
            'settings' => [
                'currency_code' => 'RUB',
            ],
        ]);

        $currency = app(CurrencyService::class)->preferredCurrency($user);

        $this->assertSame('UZS', $currency['code']);
    }
}
