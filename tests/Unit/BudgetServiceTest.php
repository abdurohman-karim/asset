<?php

namespace Tests\Unit;

use App\Models\Transaction;
use App\Models\User;
use App\Services\BudgetService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_recalculate_uses_elapsed_days_for_current_month(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 13, 12));

        $user = User::factory()->create();

        Transaction::create([
            'user_id' => $user->id,
            'amount' => -1300,
            'category' => 'Food',
            'datetime' => Carbon::now()->startOfMonth()->addDays(1),
        ]);

        $result = app(BudgetService::class)->recalculate([
            'month' => '2026-05',
        ], $user);

        $this->assertSame(100.0, $result['recommended_daily_limit']);

        Carbon::setTestNow();
    }
}
