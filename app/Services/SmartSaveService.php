<?php

namespace App\Services;

use App\Models\User;
use App\Models\Goal;
use App\Models\GoalPayment;
use App\Models\Transaction;
use App\Models\Budget;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SmartSaveService
{
    public function run(array $params, ?User $user = null): array
    {
        if (!$user) {
            // или резолв по user_id в params
            throw new \RuntimeException('User required');
        }

        $today = Carbon::today();
        $preview = (bool) Arr::get($params, 'preview', false);

        // 1. Берём активную цель
        $goal = Goal::where('user_id', $user->id)
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->first();

        if (!$goal) {
            return [
                'status' => 'no_goal',
                'message' => 'Нет активной цели для Smart Save',
            ];
        }

        $month = $today->format('Y-m');
        $budget = Budget::where('user_id', $user->id)
            ->where('month', $month)
            ->first();

        if (!$budget) {
            return [
                'status' => 'no_budget',
                'message' => 'Нет бюджета на этот месяц',
            ];
        }

        // 4. Считаем расходы за сегодня
        $dailyExpenses = Transaction::where('user_id', $user->id)
            ->whereBetween('datetime', [
                $today->copy()->startOfDay(),
                $today->copy()->endOfDay()
            ])
            ->where('amount', '<', 0)
            ->sum('amount');

        $dailyExpenses = abs($dailyExpenses); // делаем положительным числом

        $dailyLimit = (float) $budget->recommended_daily_limit;

        // 5. Считаем остаток
        $remaining = max(0, $dailyLimit - $dailyExpenses);

        if ($remaining <= 0) {
            return [
                'status' => 'no_spare_money',
                'message' => 'Сегодня нет безопасной суммы для отложений',
                'daily_limit' => $dailyLimit,
                'daily_expenses' => $dailyExpenses,
                'safe_save' => 0,
            ];
        }

        $factor = (float) Arr::get($params, 'factor', 0.5);
        $safeSaveAmount = round($remaining * $factor, 2);

        if ($safeSaveAmount < 1000) {
            return [
                'status' => 'too_small',
                'message' => 'Остаток слишком мал для отложений',
                'daily_limit' => $dailyLimit,
                'daily_expenses' => $dailyExpenses,
                'safe_save' => 0,
            ];
        }

        $availableBalance = $this->getAvailableBalance($user, $today);
        $remainingToGoal = max(0, (float) $goal->amount_total - (float) $goal->amount_saved);

        if ($preview) {
            return [
                'status' => 'preview',
                'safe_save' => $safeSaveAmount,
                'daily_limit' => $dailyLimit,
                'daily_expenses' => $dailyExpenses,
                'available_balance' => $availableBalance,
                'remaining_to_goal' => $remainingToGoal,
                'goal'  => [
                    'id' => $goal->id,
                    'title' => $goal->title,
                    'amount_total' => (float) $goal->amount_total,
                    'amount_saved' => (float) $goal->amount_saved,
                    'progress' => $goal->progress,
                ],
            ];
        }

        if ($this->alreadySavedToday($user, $today)) {
            return [
                'status' => 'already_saved',
            ];
        }

        if ((float) $goal->amount_saved >= (float) $goal->amount_total) {
            return [
                'status' => 'goal_completed',
            ];
        }

        if ($safeSaveAmount > $remainingToGoal) {
            $safeSaveAmount = $remainingToGoal;
        }

        if ($safeSaveAmount <= 0) {
            return [
                'status' => 'goal_completed',
            ];
        }

        if ($availableBalance <= 0 || $safeSaveAmount > $availableBalance) {
            return [
                'status' => 'insufficient_balance',
                'available_balance' => $availableBalance,
            ];
        }

        // 6. Записываем отложение
        $deposited = 0.0;
        $statusOverride = null;
        DB::transaction(function () use ($goal, $safeSaveAmount, $today, &$deposited, &$statusOverride) {
            $goal = Goal::whereKey($goal->id)
                ->lockForUpdate()
                ->firstOrFail();

            $remainingToGoal = max(0, (float) $goal->amount_total - (float) $goal->amount_saved);
            if ($remainingToGoal <= 0) {
                $statusOverride = 'goal_completed';
                return;
            }

            $alreadySaved = GoalPayment::where('goal_id', $goal->id)
                ->where('method', 'smart_save')
                ->whereDate('created_at', $today)
                ->exists();

            if ($alreadySaved) {
                $statusOverride = 'already_saved';
                return;
            }

            $depositAmount = min($safeSaveAmount, $remainingToGoal);
            if ($depositAmount <= 0) {
                $statusOverride = 'goal_completed';
                return;
            }

            $goal->payments()->create([
                'amount' => $depositAmount,
                'method' => 'smart_save',
                'created_at' => $today,
                'updated_at' => $today,
            ]);

            $goal->amount_saved += $depositAmount;

            if ($goal->amount_saved >= $goal->amount_total) {
                $goal->status = 'completed';
            }

            $goal->save();
            $deposited = $depositAmount;
        });

        $goal->refresh();

        if ($deposited <= 0) {
            return [
                'status' => $statusOverride ?: 'already_saved',
            ];
        }

        return [
            'status' => 'ok',
            'deposited' => $deposited,
            'new_goal_amount' => (float) $goal->amount_saved,
            'goal_progress' => $goal->progress,
            'already_saved_today' => false,
            'goal'  => [
                'id' => $goal->id,
                'title' => $goal->title,
                'amount_total' => (float) $goal->amount_total,
                'amount_saved' => (float) $goal->amount_saved,
                'progress' => $goal->progress,
            ],
        ];
    }

    protected function alreadySavedToday(User $user, Carbon $today): bool
    {
        return GoalPayment::whereDate('created_at', $today)
            ->where('method', 'smart_save')
            ->whereIn('goal_id', Goal::where('user_id', $user->id)->select('id'))
            ->exists();
    }

    protected function getAvailableBalance(User $user, Carbon $today): float
    {
        $start = $today->copy()->startOfMonth();
        $end = $today->copy()->endOfDay();

        $income = Transaction::where('user_id', $user->id)
            ->whereBetween('datetime', [$start, $end])
            ->where('amount', '>', 0)
            ->sum('amount');

        $expense = Transaction::where('user_id', $user->id)
            ->whereBetween('datetime', [$start, $end])
            ->where('amount', '<', 0)
            ->sum('amount');

        return round((float) $income - abs((float) $expense), 2);
    }
}
