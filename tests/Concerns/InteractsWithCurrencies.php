<?php

namespace Tests\Concerns;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait InteractsWithCurrencies
{
    protected function createCurrenciesTable(): void
    {
        if (Schema::hasTable('currencies')) {
            return;
        }

        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('symbol')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->nullable();
            $table->timestamps();
        });
    }

    protected function seedCurrencies(): void
    {
        foreach ([
            [
                'code' => 'UZS',
                'name' => 'Uzbekistan Som',
                'symbol' => 'сум',
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'RUB',
                'name' => 'Russian Ruble',
                'symbol' => 'RUB',
                'is_active' => false,
                'is_default' => false,
            ],
        ] as $currency) {
            DB::table('currencies')->updateOrInsert(
                ['code' => $currency['code']],
                array_merge($currency, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    protected function setUserCurrency(User $user, string $currencyCode): User
    {
        $settings = $user->settings ?? [];
        $settings['currency_code'] = $currencyCode;
        $user->settings = $settings;
        $user->save();

        return $user->fresh();
    }
}
