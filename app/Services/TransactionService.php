<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class TransactionService
{
    public function __construct(
        protected CurrencyService $currencies,
    ) {
    }

    public function import(array $params, ?User $user = null): array
    {
        if (!$user) {
            throw new \RuntimeException('User required');
        }

        $items = Arr::get($params, 'items', []);
        $created = 0;

        foreach ($items as $item) {
            $currency = $this->currencies->resolveSelection($item, $user);

            Transaction::create([
                'user_id' => $user->id,
                'amount' => Arr::get($item, 'amount'),
                'currency_code' => $currency['code'],
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

        $selectedCurrency = $this->currencies->preferredCurrency($user);
        $summaryByCurrency = [];

        $items = $transactions->map(function (Transaction $t) use (&$summaryByCurrency) {
            $currency = $this->currencies->currencyForStoredCode($t->currency_code);
            $code = $currency['code'];

            if (!isset($summaryByCurrency[$code])) {
                $summaryByCurrency[$code] = [
                    'currency' => $this->currencies->serialize($currency),
                    'income' => 0.0,
                    'expense' => 0.0,
                ];
            }

            if ($t->amount > 0) {
                $summaryByCurrency[$code]['income'] += (float) $t->amount;
            } else {
                $summaryByCurrency[$code]['expense'] += abs((float) $t->amount);
            }

            return [
                'id' => $t->id,
                'amount' => (float) $t->amount,
                'currency_code' => $currency['code'],
                'currency' => $this->currencies->serialize($currency),
                'category' => $t->category,
                'description' => $t->description,
                'datetime' => $t->datetime?->toDateTimeString(),
            ];
        })->all();

        $summary = collect($summaryByCurrency)
            ->map(function (array $group) {
                $group['balance'] = round((float) $group['income'] - (float) $group['expense'], 2);
                $group['income'] = round((float) $group['income'], 2);
                $group['expense'] = round((float) $group['expense'], 2);

                return $group;
            })
            ->values()
            ->all();

        $selectedTotals = collect($summary)->firstWhere('currency.code', $selectedCurrency['code']) ?? [
            'currency' => $this->currencies->serialize($selectedCurrency),
            'income' => 0.0,
            'expense' => 0.0,
            'balance' => 0.0,
        ];

        return [
            'date' => $day->toDateString(),
            'currency' => $selectedTotals['currency'],
            'income' => (float) $selectedTotals['income'],
            'expense' => (float) $selectedTotals['expense'],
            'balance' => (float) $selectedTotals['balance'],
            'summary_by_currency' => $summary,
            'items' => $items,
        ];
    }
}
