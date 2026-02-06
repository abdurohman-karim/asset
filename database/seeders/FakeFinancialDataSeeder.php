<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\Goal;
use App\Models\GoalPayment;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FakeFinancialDataSeeder extends Seeder
{
    private const TG_USER_ID = 7416297446;

    public function run(): void
    {
        mt_srand(self::TG_USER_ID);

        DB::transaction(function () {
            $user = $this->resolveUser();
            $this->clearUserData($user->id);

            $goals = $this->seedGoals($user->id);

            $this->seedTransactions($user->id);

            $this->seedGoalPayments($goals);

            $this->seedBudget($user->id);
        });
    }

    private function resolveUser(): User
    {
        return User::firstOrCreate(
            ['tg_user_id' => self::TG_USER_ID],
            [
                'name' => 'Finora Test User',
                'email' => 'tg_' . self::TG_USER_ID . '@local',
                'password' => Hash::make(Str::random(16)),
                'settings' => [],
                'language' => 'ru',
            ]
        );
    }

    private function clearUserData(int $userId): void
    {
        $goalIds = Goal::where('user_id', $userId)->pluck('id');

        if ($goalIds->isNotEmpty()) {
            GoalPayment::whereIn('goal_id', $goalIds)->delete();
        }

        Goal::where('user_id', $userId)->delete();
        Transaction::where('user_id', $userId)->delete();
        Budget::where('user_id', $userId)->delete();
    }

    private function seedGoals(int $userId): array
    {
        $macbook = Goal::create([
            'user_id' => $userId,
            'title' => 'MacBook',
            'amount_total' => 30000000,
            'amount_saved' => 0,
            'deadline' => Carbon::today()->addMonths(6)->endOfMonth(),
            'priority' => 1,
            'status' => 'active',
            'is_primary' => true,
        ]);

        $emergency = Goal::create([
            'user_id' => $userId,
            'title' => 'Emergency Fund',
            'amount_total' => 50000000,
            'amount_saved' => 0,
            'deadline' => Carbon::today()->addMonths(12)->endOfMonth(),
            'priority' => 2,
            'status' => 'active',
            'is_primary' => false,
        ]);

        return [
            'macbook' => $macbook,
            'emergency' => $emergency,
        ];
    }

    private function seedGoalPayments(array $goals): void
    {
        $today = Carbon::today();

        $payments = [
            // MacBook contributions (irregular, with a stagnation gap in November)
            ['goal' => 'macbook', 'date' => $today->copy()->subMonthsNoOverflow(4)->setDay(12), 'amount' => 1500000],
            ['goal' => 'macbook', 'date' => $today->copy()->subMonthsNoOverflow(2)->setDay(16), 'amount' => 2500000],
            ['goal' => 'macbook', 'date' => $today->copy()->subMonthsNoOverflow(1)->setDay(8),  'amount' => 3000000],
            ['goal' => 'macbook', 'date' => $today->copy()->setDay(2), 'amount' => 1000000],
            ['goal' => 'macbook', 'date' => $today->copy()->setDay(5), 'amount' => 2000000],

            // Emergency fund contributions (larger, disciplined period in December)
            ['goal' => 'emergency', 'date' => $today->copy()->subMonthsNoOverflow(4)->setDay(20), 'amount' => 3000000],
            ['goal' => 'emergency', 'date' => $today->copy()->subMonthsNoOverflow(2)->setDay(18), 'amount' => 4000000],
            ['goal' => 'emergency', 'date' => $today->copy()->subMonthsNoOverflow(1)->setDay(11), 'amount' => 5000000],
            ['goal' => 'emergency', 'date' => $today->copy()->setDay(4), 'amount' => 2000000],
            ['goal' => 'emergency', 'date' => $today->copy()->setDay(6), 'amount' => 4000000],
        ];

        foreach ($payments as $payment) {
            $goal = $goals[$payment['goal']];

            $goal->payments()->create([
                'amount' => $payment['amount'],
                'method' => 'manual',
                'created_at' => $payment['date'],
                'updated_at' => $payment['date'],
            ]);

            $goal->amount_saved += $payment['amount'];

            if ($goal->amount_saved >= $goal->amount_total) {
                $goal->status = 'completed';
            }

            $goal->updated_at = $payment['date'];
            $goal->save();
        }
    }

    private function seedTransactions(int $userId): void
    {
        $today = Carbon::today();
        $start = $today->copy()->subMonthsNoOverflow(4)->startOfMonth();

        $expenseRatios = [0.55, 0.68, 0.52, 0.72, 0.60];
        $incomeBases = [20000000, 20800000, 19700000, 22000000, 21200000];
        $bonusMonths = [1, 3];

        $categories = [
            ['label' => '🍔 Еда', 'weight' => 0.26],
            ['label' => '🚌 Транспорт', 'weight' => 0.14],
            ['label' => '🛒 Супермаркет', 'weight' => 0.16],
            ['label' => '📦 Покупки', 'weight' => 0.17],
            ['label' => '💳 Подписки', 'weight' => 0.08],
            ['label' => '🎉 Развлечения', 'weight' => 0.10],
            ['label' => '🏠 Коммунальные', 'weight' => 0.09],
        ];

        $monthCursor = $start->copy();
        $monthIndex = 0;

        $spikeDates = [
            $today->copy()->subMonthsNoOverflow(3)->setDay(23)->toDateString(),
            $today->copy()->subMonthsNoOverflow(1)->setDay(19)->toDateString(),
            $today->copy()->setDay(3)->toDateString(),
        ];

        while ($monthCursor->lte($today)) {
            $monthStart = $monthCursor->copy()->startOfMonth();
            $monthEnd = $monthCursor->copy()->endOfMonth();
            if ($monthEnd->gt($today)) {
                $monthEnd = $today->copy();
            }

            $incomeBase = $incomeBases[$monthIndex] ?? 21000000;
            $salary = $incomeBase + $this->randFloat(-300000, 300000);
            $salaryDay = $this->randInt(5, 10);
            $salaryDate = $monthStart->copy()->addDays($salaryDay - 1);

            if ($salaryDate->gt($monthEnd)) {
                $salaryDate = $monthEnd->copy();
            }

            if ($salaryDate->lte($monthEnd)) {
                Transaction::create([
                    'user_id' => $userId,
                    'amount' => round($salary, 2),
                    'category' => '💼 Зарплата',
                    'description' => 'Основная зарплата',
                    'datetime' => $salaryDate->copy()->setTime(10, $this->randInt(0, 59)),
                    'raw' => ['seed' => 'salary'],
                ]);
            }

            if (in_array($monthIndex, $bonusMonths, true)) {
                $bonusDate = $monthStart->copy()->addDays($this->randInt(12, 18));
                if ($bonusDate->lte($monthEnd)) {
                    Transaction::create([
                        'user_id' => $userId,
                        'amount' => round($this->randFloat(1000000, 2500000), 2),
                        'category' => '🎁 Подарок',
                        'description' => 'Разовая премия',
                        'datetime' => $bonusDate->copy()->setTime(14, $this->randInt(0, 59)),
                        'raw' => ['seed' => 'bonus'],
                    ]);
                }
            }

            if ($monthIndex === 2) {
                $extraIncomeDate = $monthStart->copy()->addDays(7);
                Transaction::create([
                    'user_id' => $userId,
                    'amount' => round($this->randFloat(600000, 900000), 2),
                    'category' => '🏦 Перевод',
                    'description' => 'Небольшое возмещение',
                    'datetime' => $extraIncomeDate->copy()->setTime(12, 15),
                    'raw' => ['seed' => 'refund'],
                ]);
            }

            $trackingDays = $this->pickTrackingDays($monthStart, $monthEnd, $this->randInt(15, 25));
            $trackingCount = count($trackingDays);

            $monthlyExpenseTarget = round($salary * ($expenseRatios[$monthIndex] ?? 0.6), 2);
            $baseDaily = $trackingCount > 0 ? $monthlyExpenseTarget / $trackingCount : 0;

            foreach ($trackingDays as $day) {
                $multiplier = $this->randFloat(0.85, 1.25);

                if ($day->isWeekend()) {
                    $multiplier *= 1.25;
                }

                if ((int) $monthStart->format('m') === 12 && $day->day >= 10 && $day->day <= 16) {
                    $multiplier *= 0.6; // disciplined week
                }

                if (in_array($day->toDateString(), $spikeDates, true)) {
                    $multiplier *= $this->randFloat(2.8, 3.8);
                }

                $dailyTotal = max(20000, round($baseDaily * $multiplier, 2));
                $this->createDailyExpenses($userId, $day, $dailyTotal, $categories);
            }

            $monthCursor->addMonth();
            $monthIndex++;
        }
    }

    private function seedBudget(int $userId): void
    {
        $today = Carbon::today();
        $month = $today->format('Y-m');
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        $income = Transaction::where('user_id', $userId)
            ->whereBetween('datetime', [$monthStart, $monthEnd])
            ->where('amount', '>', 0)
            ->sum('amount');

        $expense = Transaction::where('user_id', $userId)
            ->whereBetween('datetime', [$monthStart, $monthEnd])
            ->where('amount', '<', 0)
            ->sum('amount');

        $expense = abs($expense);

        $daysElapsed = max(1, $today->day);
        $recommendedDaily = round($expense / $daysElapsed, 2);

        Budget::updateOrCreate(
            ['user_id' => $userId, 'month' => $month],
            [
                'income' => $income,
                'expenses' => $expense,
                'recommended_daily_limit' => $recommendedDaily,
                'categories' => [],
            ]
        );
    }

    private function pickTrackingDays(Carbon $start, Carbon $end, int $count): array
    {
        $days = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $days[] = $cursor->copy();
            $cursor->addDay();
        }

        shuffle($days);
        $selected = array_slice($days, 0, min($count, count($days)));

        usort($selected, fn (Carbon $a, Carbon $b) => $a->lessThan($b) ? -1 : 1);

        return $selected;
    }

    private function createDailyExpenses(int $userId, Carbon $day, float $total, array $categories): void
    {
        $txCount = $this->randInt(1, 3);
        $remaining = $total;

        for ($i = 1; $i <= $txCount; $i++) {
            if ($i === $txCount) {
                $amount = $remaining;
            } else {
                $ratio = $this->randFloat(0.3, 0.6);
                $amount = round($remaining * $ratio, 2);
                $remaining -= $amount;
            }

            $category = $this->pickCategory($categories);

            Transaction::create([
                'user_id' => $userId,
                'amount' => -abs($amount),
                'category' => $category,
                'description' => $this->expenseDescription($category),
                'datetime' => $day->copy()->setTime($this->randInt(8, 21), $this->randInt(0, 59)),
                'raw' => ['seed' => 'expense'],
            ]);
        }
    }

    private function pickCategory(array $categories): string
    {
        $roll = $this->randFloat(0, 1);
        $accum = 0;

        foreach ($categories as $cat) {
            $accum += $cat['weight'];
            if ($roll <= $accum) {
                return $cat['label'];
            }
        }

        return $categories[array_key_last($categories)]['label'];
    }

    private function expenseDescription(string $category): string
    {
        return match ($category) {
            '🍔 Еда' => 'Обед или кофе',
            '🚌 Транспорт' => 'Проезд и такси',
            '🛒 Супермаркет' => 'Покупки в супермаркете',
            '📦 Покупки' => 'Покупки для дома',
            '💳 Подписки' => 'Подписки и сервисы',
            '🎉 Развлечения' => 'Досуг и отдых',
            '🏠 Коммунальные' => 'Коммунальные услуги',
            default => 'Расход',
        };
    }

    private function randInt(int $min, int $max): int
    {
        return mt_rand($min, $max);
    }

    private function randFloat(float $min, float $max): float
    {
        return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }
}
