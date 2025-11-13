<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class TransactionService
{
    public function import(array $params, ?User $user = null): array
    {
        if (!$user) {
            throw new \RuntimeException('User required');
        }

        $items = Arr::get($params, 'items', []);
        $created = 0;

        foreach ($items as $item) {
            Transaction::create([
                'user_id' => $user->id,
                'amount' => Arr::get($item, 'amount'),
                'category' => Arr::get($item, 'category'),
                'description' => Arr::get($item, 'description'),
                'datetime' => Arr::get($item, 'datetime', now()),
                'raw' => $item,
            ]);

            $created++;
        }

        return [
            'imported' => $created,
        ];
    }

    public function getDaily(array $params, ?User $user = null): array
    {
        if (!$user) {
            throw new \RuntimeException('User required');
        }

        $date = Arr::get($params, 'date');
        $day = $date ? Carbon::parse($date) : Carbon::today();

        $transactions = Transaction::where('user_id', $user->id)
            ->whereBetween('datetime', [
                $day->copy()->startOfDay(),
                $day->copy()->endOfDay()
            ])
            ->orderBy('datetime')
            ->get();

        $sumIncome = 0;
        $sumExpense = 0;

        $items = $transactions->map(function (Transaction $t) use (&$sumIncome, &$sumExpense) {
            if ($t->amount > 0) {
                $sumIncome += $t->amount;
            } else {
                $sumExpense += abs($t->amount);
            }

            return [
                'id' => $t->id,
                'amount' => (float) $t->amount,
                'category' => $t->category,
                'description' => $t->description,
                'datetime' => $t->datetime?->toDateTimeString(),
            ];
        })->all();

        return [
            'date' => $day->toDateString(),
            'income' => (float) $sumIncome,
            'expense' => (float) $sumExpense,
            'items' => $items,
        ];
    }
}
