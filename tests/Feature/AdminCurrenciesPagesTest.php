<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCurrenciesPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_currencies_index(): void
    {
        $admin = $this->makeSuperAdmin();

        $response = $this->actingAs($admin)->get(route('currencies.index'));

        $response->assertOk();
        $response->assertSee('Валюты');
        $response->assertSee('UZS');
    }

    public function test_super_admin_can_create_currency_and_code_is_saved_uppercase(): void
    {
        $admin = $this->makeSuperAdmin();

        $response = $this->actingAs($admin)->post(route('currencies.store'), [
            'code' => 'usd',
            'name' => 'US Dollar',
            'symbol' => '$',
            'is_active' => '1',
            'sort_order' => 2,
        ]);

        $response->assertRedirect(route('currencies.index'));

        $this->assertDatabaseHas('currencies', [
            'code' => 'USD',
            'name' => 'US Dollar',
        ]);
    }

    public function test_only_one_default_currency_exists_after_creating_new_default(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)->post(route('currencies.store'), [
            'code' => 'usd',
            'name' => 'US Dollar',
            'symbol' => '$',
            'is_active' => '1',
            'is_default' => '1',
        ]);

        $this->assertSame(1, Currency::query()->where('is_default', true)->count());
        $this->assertDatabaseHas('currencies', [
            'code' => 'USD',
            'is_default' => true,
        ]);
        $this->assertDatabaseHas('currencies', [
            'code' => 'UZS',
            'is_default' => false,
        ]);
    }

    public function test_currency_used_in_financial_records_cannot_be_deleted(): void
    {
        $admin = $this->makeSuperAdmin();

        $currency = Currency::create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'is_active' => true,
            'is_default' => false,
        ]);

        $user = User::factory()->create();

        Transaction::create([
            'user_id' => $user->id,
            'amount' => 100,
            'currency_code' => 'USD',
            'category' => 'Salary',
            'datetime' => now(),
        ]);

        $response = $this->actingAs($admin)->delete(route('currencies.destroy', $currency));

        $response->assertRedirect();
        $this->assertDatabaseHas('currencies', [
            'id' => $currency->id,
            'code' => 'USD',
        ]);
    }

    protected function makeSuperAdmin(): User
    {
        $role = Role::firstOrCreate(['name' => 'Super Admin'], ['guard_name' => 'web']);
        $user = User::factory()->create([
            'name' => 'Super Admin User',
            'email' => 'currencies-admin@example.com',
            'phone' => '977777777',
            'is_admin' => true,
        ]);

        $user->syncRoles([$role->id]);

        return $user;
    }
}
