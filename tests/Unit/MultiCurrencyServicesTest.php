<?php

namespace Tests\Unit;

use App\Models\Goal;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AIService;
use App\Services\BudgetService;
use App\Services\GoalService;
use App\Services\SmartSaveService;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithCurrencies;
use Tests\TestCase;

class MultiCurrencyServicesTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithCurrencies;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createCurrenciesTable();
        $this->seedCurrencies();
    }

    public function test_transaction_import_and_goal_create_use_selected_currency(): void
    {
        $user = $this->setUserCurrency(User::factory()->create(), 'USD');

        app(TransactionService::class)->import([
            'items' => [[
                'amount' => -25,
                'category' => 'Food',
                'datetime' => now(),
            ]],
        ], $user);

        $goal = app(GoalService::class)->create([
            'title' => 'Laptop',
            'amount_total' => 1500,
        ], $user);

        $this->assertSame('USD', Transaction::first()->currency_code);
        $this->assertSame('USD', Goal::first()->currency_code);
        $this->assertSame('USD', $goal['currency_code']);
    }

    public function test_budget_recalculate_uses_selected_currency_only(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 13, 12));

        $user = $this->setUserCurrency(User::factory()->create(), 'USD');

        Transaction::create([
            'user_id' => $user->id,
            'amount' => 1000,
            'currency_code' => 'USD',
            'category' => 'Salary',
            'datetime' => Carbon::now()->startOfMonth()->addDay(),
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'amount' => -100,
            'currency_code' => 'USD',
            'category' => 'Food',
            'datetime' => Carbon::now()->startOfMonth()->addDays(2),
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'amount' => -500000,
            'currency_code' => 'UZS',
            'category' => 'Food',
            'datetime' => Carbon::now()->startOfMonth()->addDays(2),
        ]);

        $budget = app(BudgetService::class)->recalculate([
            'month' => '2026-05',
        ], $user);

        $this->assertSame('USD', $budget['currency_code']);
        $this->assertSame(1000.0, $budget['income']);
        $this->assertSame(100.0, $budget['expenses']);

        Carbon::setTestNow();
    }

    public function test_old_transactions_without_currency_fallback_to_uzs(): void
    {
        $user = User::factory()->create();

        Transaction::create([
            'user_id' => $user->id,
            'amount' => -5000,
            'currency_code' => null,
            'category' => 'Food',
            'datetime' => now(),
        ]);

        $daily = app(TransactionService::class)->getDaily([], $user);

        $this->assertSame('UZS', $daily['items'][0]['currency_code']);
        $this->assertSame('UZS', $daily['summary_by_currency'][0]['currency']['code']);
    }

    public function test_ai_context_includes_selected_currency(): void
    {
        $user = $this->setUserCurrency(User::factory()->create(['language' => 'en']), 'USD');

        $service = new class(app(\App\Services\CurrencyService::class)) extends AIService {
            public function exposeContext(User $user): array
            {
                return $this->buildContextPackage($user, null);
            }
        };

        $context = $service->exposeContext($user);

        $this->assertSame('USD', $context['user']['selected_currency']['code']);
        $this->assertSame('USD', $context['analysis_currency']['code']);
    }

    public function test_smart_save_does_not_mix_goal_currency_with_selected_currency(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 13, 12));

        $user = $this->setUserCurrency(User::factory()->create(), 'USD');

        Goal::create([
            'user_id' => $user->id,
            'title' => 'Vacation',
            'amount_total' => 1000,
            'amount_saved' => 100,
            'currency_code' => 'UZS',
            'priority' => 1,
            'status' => 'active',
        ]);

        $result = app(SmartSaveService::class)->run(['preview' => true], $user);

        $this->assertSame('currency_mismatch', $result['status']);
        $this->assertSame('USD', $result['currency']['code']);
        $this->assertSame('UZS', $result['goal_currency']['code']);

        Carbon::setTestNow();
    }
}
