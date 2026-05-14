<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\User;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class BudgetService
{
    public function __construct(
        protected CurrencyService $currencies,
    ) {
    }

    public function getMonth(array $params, ?User $user = null): array
    {
        if (!$user) {
            throw new \RuntimeException('User required');
        }

        $month = Arr::get($params, 'month') ?: Carbon::today()->format('Y-m');
        $currency = $this->currencies->resolveSelection($params, $user);

        $budget = Budget::where('user_id', $user->id)
            ->where('month', $month)
            ->where('currency_code', $currency['code'])
            ->first();

        if (!$budget) {
            return [
                'exists' => false,
                'month'  => $month,
                'currency' => $this->currencies->serialize($currency),
            ];
        }

        return $this->transform($budget);
    }

    public function recalculate(array $params, ?User $user = null): array
    {
        if (!$user) {
            throw new \RuntimeException('User required');
        }

        $month = Arr::get($params, 'month') ?: Carbon::today()->format('Y-m');
        $currency = $this->currencies->resolveSelection($params, $user);

        [$year, $monthNum] = explode('-', $month);
        $start = Carbon::createFromDate($year, $monthNum, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $income = Transaction::where('user_id', $user->id)
            ->where('currency_code', $currency['code'])
            ->whereBetween('datetime', [$start, $end])
            ->where('amount', '>', 0)
            ->sum('amount');

        $expenses = Transaction::where('user_id', $user->id)
            ->where('currency_code', $currency['code'])
            ->whereBetween('datetime', [$start, $end])
            ->where('amount', '<', 0)
            ->sum('amount');

        $expenses = abs($expenses);

        $isCurrentMonth = $month === Carbon::today()->format('Y-m');
        $daysInPeriod = $isCurrentMonth
            ? min(Carbon::today()->day, $start->daysInMonth)
            : $start->daysInMonth;

        // Keep the existing API contract: recommended_daily_limit reflects average daily
        // spending for the selected month so far, or the full month for past periods.
        $recommendedDailyLimit = $daysInPeriod > 0 ? round($expenses / $daysInPeriod, 2) : 0;

        $budget = Budget::updateOrCreate(
            ['user_id' => $user->id, 'month' => $month, 'currency_code' => $currency['code']],
            [
                'currency_code'          => $currency['code'],
                'income'                 => $income,
                'expenses'               => $expenses,
                'recommended_daily_limit'=> $recommendedDailyLimit,
            ]
        );

        return $this->transform($budget);
    }

    protected function transform(Budget $budget): array
    {
        return [
            'id' => $budget->id,
            'month' => $budget->month,
            'currency_code' => $budget->currency_code ?: User::defaultCurrency()['code'],
            'currency' => $this->currencies->serialize(
                $this->currencies->currencyForStoredCode($budget->currency_code)
            ),
            'income' => (float) $budget->income,
            'expenses' => (float) $budget->expenses,
            'recommended_daily_limit'=> (float) $budget->recommended_daily_limit,
            'categories' => $budget->categories ?? [],
        ];
    }
}
