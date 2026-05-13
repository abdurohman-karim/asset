<?php

namespace App\Http\Controllers\Blade;

use App\Models\Role;
use App\Models\User;
use App\Services\Check;
use App\Services\Helper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    public function index(Request $request)
    {
        Check::permission('Просмотр пользователей');

        $search = trim((string) $request->string('search'));
        $sort = (string) $request->string('sort', 'id');
        $direction = strtolower((string) $request->string('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = [
            'id',
            'name',
            'email',
            'phone',
            'created_at',
            'transactions_count',
            'goals_count',
            'last_transaction_at',
            'last_ai_insight_at',
        ];

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        $users = User::query()
            ->select('users.*')
            ->with(['roles:id,name'])
            ->withCount([
                'transactions',
                'budgets',
                'goals',
                'goalPayments',
                'aiInsights',
            ])
            ->withMax('transactions as last_transaction_at', 'datetime')
            ->withMax('goalPayments as last_goal_payment_at', 'goal_payments.created_at')
            ->withMax('aiInsights as last_ai_insight_at', 'created_at')
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    foreach (['name', 'email', 'phone', 'tg_user_id'] as $column) {
                        $query->orWhere($column, 'like', "%{$search}%");
                    }
                });
            })
            ->orderBy($sort, $direction)
            ->orderByDesc('id')
            ->paginate(15);

        $stats = [
            'total_users' => User::count(),
            'admin_users' => User::where('is_admin', true)->count(),
            'super_admins' => User::whereHas('roles', function (Builder $query) {
                $query->where('name', 'Super Admin');
            })->count(),
            'users_with_transactions' => User::has('transactions')->count(),
            'users_with_goals' => User::has('goals')->count(),
            'users_with_ai_insights' => User::has('aiInsights')->count(),
        ];

        return view('pages.users.index', compact('users', 'stats', 'search', 'sort', 'direction'));
    }

    public function create()
    {
        Check::permission('Создать пользователя');
        $roles = Role::all();
        return view('pages.users.create',compact('roles'));
    }

    public function store(Request $request)
    {
        Check::permission('Создать пользователя');
        $this->validate($request,[
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $user = new User();
        $user->fill($request->all());
        $user->phone = Helper::phoneFormatDB($request->get('phone'));
        $user->password = Hash::make($request->password);
        $user->save();

        if (auth()->user()->hasPermission('Установить роль для пользователя') && $request->has('roles'))
        {
            $user->syncRoles($request->roles);
        }
        return redirect()->route('users.index')->with('success',"Создан новый пользователь с именем $user->name!");
    }

    public function edit($id)
    {
        if (auth()->id() != $id)
            Check::permission('Редактировать пользователя');

        $roles = auth()->user()->hasPermission('Установить роль для пользователя') ? Role::all():[];

        $user = User::whereId($id)->with(['roles'])->firstOrFail();
        $user->roles = array_flip($user->roles->map(function ($role){
            return $role->name;
        })->toArray());

        return view('pages.users.edit',compact('user','roles'));
    }

    public function show(User $user)
    {
        Check::permission('Просмотр пользователей');

        $user->load([
            'roles.permissions:id,name',
            'permissions:id,name',
        ]);
        $user->loadCount([
            'transactions',
            'budgets',
            'goals',
            'goalPayments',
            'aiInsights',
        ]);

        $currentMonth = now()->format('Y-m');
        $currentBudget = $user->budgets()
            ->where('month', $currentMonth)
            ->first();

        $budgetHistory = $user->budgets()
            ->orderByDesc('month')
            ->limit(6)
            ->get();

        $latestTransactions = $user->transactions()
            ->orderByDesc('datetime')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'user_id', 'amount', 'category', 'description', 'datetime', 'raw', 'created_at']);

        $latestGoals = $user->goals()
            ->withCount('payments')
            ->withSum('payments', 'amount')
            ->withMax('payments as latest_payment_at', 'created_at')
            ->latest('updated_at')
            ->limit(10)
            ->get();

        $latestGoalPayments = $user->goalPayments()
            ->with('goal:id,title,user_id')
            ->latest('goal_payments.created_at')
            ->limit(10)
            ->get();

        $latestAiInsights = $user->aiInsights()
            ->latest()
            ->limit(10)
            ->get(['id', 'user_id', 'type', 'insight', 'metadata', 'created_at']);

        $effectivePermissions = $user->permissions
            ->pluck('name')
            ->merge(
                $user->roles->flatMap(function ($role) {
                    return $role->permissions->pluck('name');
                })
            )
            ->unique()
            ->sort()
            ->values();

        $latestTransactionAt = $user->transactions()->max('datetime');
        $latestGoalPaymentAt = $user->goalPayments()->max('goal_payments.created_at');
        $latestAiInsightAt = $user->aiInsights()->max('created_at');

        $activity = [
            'latest_activity_at' => collect([
                $latestTransactionAt,
                $latestGoalPaymentAt,
                $latestAiInsightAt,
                $currentBudget?->updated_at,
                $user->updated_at,
            ])->filter()->max(),
            'latest_transaction_at' => $latestTransactionAt,
            'latest_goal_payment_at' => $latestGoalPaymentAt,
            'latest_ai_insight_at' => $latestAiInsightAt,
            'total_income' => $user->transactions()->where('amount', '>', 0)->sum('amount'),
            'total_expenses' => abs((float) $user->transactions()->where('amount', '<', 0)->sum('amount')),
            'total_goal_payments' => $user->goalPayments()->sum('amount'),
        ];

        return view('pages.users.show', compact(
            'user',
            'currentBudget',
            'budgetHistory',
            'latestTransactions',
            'latestGoals',
            'latestGoalPayments',
            'latestAiInsights',
            'effectivePermissions',
            'activity'
        ));
    }


    public function update(Request $request, User $user)
    {
        if ($request->has('phone')) {
            $request->merge(['phone' => Helper::phoneFormatDB($request->get('phone'))]);
        }

        $this->validate($request, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', "unique:users,email,{$user->id}"],
            'phone' => ['required', 'min:9', 'string', "unique:users,phone,{$user->id}"],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $data = $request->only(['name', 'email', 'phone']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->fill($data)->save();

        if (auth()->user()->hasPermission("Установить роль для пользователя")) {
            if ($request->has('roles')) {
                $user->syncRoles($request->roles);
            } else {
                $user->detachAllRoles();
            }
        }

        if (auth()->id() === $user->id) {
            return redirect()->back()->with('success', "Ваш профиль успешно обновлен!");
        }

        return redirect()->route('users.index')->with('success', "Пользователь {$user->name} успешно обновлен!");
    }

    public function destroy(User $user)
    {
        Check::permission('Удалить пользователя');
        $user->delete();
        return redirect()->back()->with('success',"Пользователь $user->name успешно удален!");
    }
}
