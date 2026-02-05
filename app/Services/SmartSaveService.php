<?php

namespace App\Services;

use App\Models\User;
use App\Models\Goal;
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

        // 6. Записываем отложение
        DB::transaction(function () use ($goal, $safeSaveAmount) {
            $goal = Goal::whereKey($goal->id)
                ->lockForUpdate()
                ->firstOrFail();

            $goal->payments()->create([
                'amount' => $safeSaveAmount,
                'method' => 'smart_save',
            ]);

            $goal->amount_saved += $safeSaveAmount;

            if ($goal->amount_saved >= $goal->amount_total) {
                $goal->status = 'completed';
            }

            $goal->save();
        });

        $goal->refresh();

        return [
            'status' => 'success',
            'message' => 'Smart Save выполнен',
            'safe_save' => $safeSaveAmount,
            'daily_limit' => $dailyLimit,
            'daily_expenses' => $dailyExpenses,
            'goal'  => [
                'id' => $goal->id,
                'title' => $goal->title,
                'amount_total' => (float) $goal->amount_total,
                'amount_saved' => (float) $goal->amount_saved,
                'progress' => $goal->progress,
            ],
        ];
    }
}
