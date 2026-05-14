<?php

namespace App\Services;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class GoalService
{
    public function __construct(
        protected CurrencyService $currencies,
    ) {
    }

    public function create(array $params, ?User $user = null): array
    {
        if (!$user) {
            throw new \RuntimeException('User not found (goal.create)');
        }

        $currency = $this->currencies->preferredCurrency($user);
        $maxPriority = Goal::where('user_id', $user->id)->max('priority');
        $priority = $maxPriority ? $maxPriority + 1 : 1;

        $goal = Goal::create([
            'user_id' => $user->id,
            'title' => Arr::get($params, 'title'),
            'amount_total' => Arr::get($params, 'amount_total'),
            'amount_saved' => 0,
            'currency_code' => $currency['code'],
            'deadline' => Arr::get($params, 'deadline'),
            'priority' => $priority,
            'status' => 'active',
        ]);

        return $this->serializeGoal($goal);
    }

    public function get(array $params, ?User $user = null): array
    {
        $goalId = Arr::get($params, 'goal_id');
        $goal = Goal::whereKey($goalId)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->firstOrFail();

        return $this->serializeGoal($goal);
    }

    public function list(array $params, ?User $user): array
    {
        if (!$user) {
            throw new \RuntimeException('User not found (goal.list)');
        }

        $goals = Goal::where('user_id', $user->id)
            ->orderBy('priority', 'asc')
            ->get()
            ->map(fn (Goal $goal) => $this->serializeGoal($goal));

        return [
            'goals' => $goals->values()->all(),
        ];
    }

    public function deposit(array $params, ?User $user = null): array
    {
        $goalId = Arr::get($params, 'goal_id');
        $amount = (float) Arr::get($params, 'amount');
        $method = Arr::get($params, 'method', 'manual');

        $goal = DB::transaction(function () use ($goalId, $user, $amount, $method) {
            $goal = Goal::whereKey($goalId)
                ->when($user, fn ($q) => $q->where('user_id', $user->id))
                ->lockForUpdate()
                ->firstOrFail();

            $goal->payments()->create([
                'amount' => $amount,
                'currency_code' => $goal->currency_code ?: User::defaultCurrency()['code'],
                'method' => $method,
            ]);

            $goal->amount_saved = $goal->amount_saved + $amount;

            if ($goal->amount_saved >= $goal->amount_total) {
                $goal->status = 'completed';
            }

            $goal->save();

            return $goal->fresh();
        });

        return $this->serializeGoal($goal);
    }

    public function setPrimary(array $params, User $user): array
    {
        $goal = Goal::where('user_id', $user->id)
            ->where('id', $params['goal_id'])
            ->firstOrFail();

        Goal::where('user_id', $user->id)->update(['is_primary' => false]);

        $goal->update(['is_primary' => true]);

        return $this->serializeGoal($goal);
    }

    public function priorityUp(array $params, User $user): array
    {
        $goal = Goal::where('user_id', $user->id)
            ->where('id', $params['goal_id'])
            ->firstOrFail();

        if ($goal->priority <= 1) {
            return $this->serializeGoal($goal);
        }

        $swap = Goal::where('user_id', $user->id)
            ->where('priority', $goal->priority - 1)
            ->first();

        if ($swap) {
            $swap->priority++;
            $swap->save();
        }

        $goal->priority--;
        $goal->save();

        $goal->refresh();

        return $this->serializeGoal($goal);
    }


    public function priorityDown(array $params, User $user): array
    {
        $goal = Goal::where('user_id', $user->id)
            ->where('id', $params['goal_id'])
            ->firstOrFail();

        $swap = Goal::where('user_id', $user->id)
            ->where('priority', $goal->priority + 1)
            ->first();

        if ($swap) {
            $swap->priority--;
            $swap->save();
        }

        $goal->priority++;
        $goal->save();

        $goal->refresh();

        return $this->serializeGoal($goal);
    }

    public function close(array $params, User $user): array
    {
        $goal = Goal::where('user_id', $user->id)
            ->where('id', $params['goal_id'])
            ->firstOrFail();

        $goal->update(['status' => 'closed']);

        return $this->serializeGoal($goal);
    }

    public function reopen(array $params, User $user): array
    {
        $goal = Goal::where('user_id', $user->id)
            ->where('id', $params['goal_id'])
            ->firstOrFail();

        $goal->update(['status' => 'active']);

        return $this->serializeGoal($goal);
    }

    private function serializeGoal(Goal $goal): array
    {
        $currency = $this->currencies->currencyForStoredCode($goal->currency_code);

        return [
            'id' => $goal->id,
            'title' => $goal->title,
            'amount_total' => (float) $goal->amount_total,
            'amount_saved' => (float) $goal->amount_saved,
            'currency_code' => $currency['code'],
            'currency' => $this->currencies->serialize($currency),
            'deadline' => optional($goal->deadline)->toDateString(),
            'priority' => $goal->priority,
            'is_primary' => $goal->is_primary,
            'status' => $goal->status,
            'progress' => $goal->progress,
            'created_at' => $goal->created_at?->toDateTimeString(),
        ];
    }
}
