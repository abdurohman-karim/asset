<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\User;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class BudgetService
{
    public function getMonth(array $params, ?User $user = null): array
    {
        if (!$user) {
            throw new \RuntimeException('User required');
        }

        $month = Arr::get($params, 'month') ?: Carbon::today()->format('Y-m');

        $budget = Budget::where('user_id', $user->id)
            ->where('month', $month)
            ->first();

        if (!$budget) {
            return [
                'exists' => false,
                'month'  => $month,
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

        [$year, $monthNum] = explode('-', $month);
        $start = Carbon::createFromDate($year, $monthNum, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $income = Transaction::where('user_id', $user->id)
            ->whereBetween('datetime', [$start, $end])
            ->where('amount', '>', 0)
            ->sum('amount');

        $expenses = Transaction::where('user_id', $user->id)
            ->whereBetween('datetime', [$start, $end])
            ->where('amount', '<', 0)
            ->sum('amount');

        $expenses = abs($expenses);

        $days = $start->daysInMonth;
        $recommendedDailyLimit = $days > 0 ? round($expenses / max(1, $start->day), 2) : 0;

        $budget = Budget::updateOrCreate(
            ['user_id' => $user->id, 'month' => $month],
            [
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
            'income' => (float) $budget->income,
            'expenses' => (float) $budget->expenses,
            'recommended_daily_limit'=> (float) $budget->recommended_daily_limit,
            'categories' => $budget->categories ?? [],
        ];
    }
}
