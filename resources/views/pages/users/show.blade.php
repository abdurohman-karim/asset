@extends('layouts.master')

@section('content')
    @php
        $theme = data_get($user->settings, 'theme');
        $settingsJson = empty($user->settings)
            ? null
            : json_encode($user->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    @endphp

    <div class="row">
        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h4 class="mb-1">{{ $user->name }}</h4>
                    <p class="text-muted mb-0">Пользователь #{{ $user->id }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('users.index') }}" class="btn btn-light">Назад к списку</a>
                    <a href="{{ route('users.edit', $user) }}" class="btn btn-success">Редактировать</a>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6 col-xl-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <p class="text-muted mb-1">Транзакции</p>
                            <h4 class="mb-1">{{ $user->transactions_count }}</h4>
                            <div class="small text-muted">Доход: {{ number_format((float) $activity['total_income'], 2, '.', ' ') }}</div>
                            <div class="small text-muted">Расход: {{ number_format((float) $activity['total_expenses'], 2, '.', ' ') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <p class="text-muted mb-1">Бюджеты и лимиты</p>
                            <h4 class="mb-1">{{ $user->budgets_count }}</h4>
                            <div class="small text-muted">Текущий месяц: {{ $currentBudget?->month ?: 'Нет данных' }}</div>
                            <div class="small text-muted">Лимит/день: {{ $currentBudget ? number_format((float) $currentBudget->recommended_daily_limit, 2, '.', ' ') : 'Нет данных' }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <p class="text-muted mb-1">Цели и накопления</p>
                            <h4 class="mb-1">{{ $user->goals_count }}</h4>
                            <div class="small text-muted">Goal payments: {{ $user->goal_payments_count }}</div>
                            <div class="small text-muted">Сумма пополнений: {{ number_format((float) $activity['total_goal_payments'], 2, '.', ' ') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <p class="text-muted mb-1">AI insights</p>
                            <h4 class="mb-1">{{ $user->ai_insights_count }}</h4>
                            <div class="small text-muted">Последняя активность: {{ optional($activity['latest_activity_at'])->format('d.m.Y H:i') ?: 'Нет данных' }}</div>
                            <div class="small text-muted">Последний insight: {{ optional($activity['latest_ai_insight_at'])->format('d.m.Y H:i') ?: 'Нет данных' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-3">Профиль</h4>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <tbody>
                                    <tr>
                                        <th class="w-25">ID</th>
                                        <td>{{ $user->id }}</td>
                                    </tr>
                                    <tr>
                                        <th>Имя</th>
                                        <td>{{ $user->name }}</td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td>{{ $user->email ?: 'Не указан' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Телефон</th>
                                        <td>{{ $user->phone ?: 'Не указан' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Telegram ID</th>
                                        <td>{{ $user->tg_user_id ?: 'Не указан' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Язык</th>
                                        <td>{{ $user->language ?: 'Не указан' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Тема</th>
                                        <td>{{ $theme ?: 'Не указана' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Админ</th>
                                        <td>{{ $user->is_admin ? 'Да' : 'Нет' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Создан</th>
                                        <td>{{ optional($user->created_at)->format('d.m.Y H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Обновлен</th>
                                        <td>{{ optional($user->updated_at)->format('d.m.Y H:i') }}</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>

                            @if($settingsJson)
                                <details class="mt-3">
                                    <summary class="fw-semibold">Настройки</summary>
                                    <pre class="small bg-light p-3 rounded mt-2 mb-0">{{ $settingsJson }}</pre>
                                </details>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-3">Роли и разрешения</h4>

                            <div class="mb-3">
                                <h5 class="font-size-14">Роли</h5>
                                @forelse($user->roles as $role)
                                    <span class="badge badge-soft-primary font-size-11 m-1">{{ $role->name }}</span>
                                @empty
                                    <p class="text-muted mb-0">У пользователя нет ролей.</p>
                                @endforelse
                            </div>

                            <div class="mb-3">
                                <h5 class="font-size-14">Прямые разрешения</h5>
                                @forelse($user->permissions as $permission)
                                    <span class="badge badge-soft-warning font-size-11 m-1">{{ $permission->name }}</span>
                                @empty
                                    <p class="text-muted mb-0">Прямые разрешения не назначены.</p>
                                @endforelse
                            </div>

                            <div>
                                <h5 class="font-size-14">Эффективные разрешения</h5>
                                @forelse($effectivePermissions as $permission)
                                    <span class="badge badge-soft-success font-size-11 m-1">{{ $permission }}</span>
                                @empty
                                    <p class="text-muted mb-0">Разрешения отсутствуют.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">Активность и лимиты</h4>
                    <div class="row g-3">
                        <div class="col-xl-4">
                            <div class="border rounded p-3 h-100">
                                <h5 class="font-size-14">Сводка</h5>
                                <div class="small mb-1">Последняя активность: {{ optional($activity['latest_activity_at'])->format('d.m.Y H:i') ?: 'Нет данных' }}</div>
                                <div class="small mb-1">Последняя транзакция: {{ optional($activity['latest_transaction_at'])->format('d.m.Y H:i') ?: 'Нет данных' }}</div>
                                <div class="small mb-1">Последний goal payment: {{ optional($activity['latest_goal_payment_at'])->format('d.m.Y H:i') ?: 'Нет данных' }}</div>
                                <div class="small">Последний AI insight: {{ optional($activity['latest_ai_insight_at'])->format('d.m.Y H:i') ?: 'Нет данных' }}</div>
                            </div>
                        </div>
                        <div class="col-xl-4">
                            <div class="border rounded p-3 h-100">
                                <h5 class="font-size-14">Текущий бюджет</h5>
                                @if($currentBudget)
                                    <div class="small mb-1">Месяц: {{ $currentBudget->month }}</div>
                                    <div class="small mb-1">Доход: {{ number_format((float) $currentBudget->income, 2, '.', ' ') }}</div>
                                    <div class="small mb-1">Расход: {{ number_format((float) $currentBudget->expenses, 2, '.', ' ') }}</div>
                                    <div class="small">Лимит/день: {{ number_format((float) $currentBudget->recommended_daily_limit, 2, '.', ' ') }}</div>
                                @else
                                    <p class="text-muted mb-0">Бюджет на текущий месяц не найден.</p>
                                @endif
                            </div>
                        </div>
                        <div class="col-xl-4">
                            <div class="border rounded p-3 h-100">
                                <h5 class="font-size-14">История бюджетов</h5>
                                @forelse($budgetHistory as $budget)
                                    <div class="small mb-2">
                                        <div class="fw-semibold">{{ $budget->month }}</div>
                                        <div class="text-muted">Доход {{ number_format((float) $budget->income, 2, '.', ' ') }}, расход {{ number_format((float) $budget->expenses, 2, '.', ' ') }}</div>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">История бюджетов отсутствует.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">Последние транзакции</h4>
                    @if($latestTransactions->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>Дата</th>
                                    <th>Сумма</th>
                                    <th>Тип</th>
                                    <th>Категория</th>
                                    <th>Описание</th>
                                    <th>Raw</th>
                                    <th>Создано</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($latestTransactions as $transaction)
                                    @php
                                        $rawJson = empty($transaction->raw)
                                            ? null
                                            : json_encode($transaction->raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                    @endphp
                                    <tr>
                                        <td>{{ optional($transaction->datetime)->format('d.m.Y H:i') ?: 'Нет даты' }}</td>
                                        <td>{{ number_format((float) $transaction->amount, 2, '.', ' ') }}</td>
                                        <td>
                                            <span class="badge {{ $transaction->isIncome() ? 'badge-soft-success' : 'badge-soft-danger' }}">
                                                {{ $transaction->isIncome() ? 'Доход' : 'Расход' }}
                                            </span>
                                        </td>
                                        <td>{{ $transaction->category ?: 'Без категории' }}</td>
                                        <td>{{ $transaction->description ?: 'Нет описания' }}</td>
                                        <td>
                                            @if($rawJson)
                                                <details>
                                                    <summary>{{ \Illuminate\Support\Str::limit($rawJson, 80) }}</summary>
                                                    <pre class="small bg-light p-2 rounded mt-2 mb-0">{{ $rawJson }}</pre>
                                                </details>
                                            @else
                                                <span class="text-muted">Нет raw</span>
                                            @endif
                                        </td>
                                        <td>{{ optional($transaction->created_at)->format('d.m.Y H:i') }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">У пользователя пока нет транзакций.</p>
                    @endif
                </div>
            </div>

            <div class="row">
                <div class="col-xl-7">
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="card-title mb-3">Цели и Smart Save</h4>
                            @if($latestGoals->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead class="table-light">
                                        <tr>
                                            <th>Цель</th>
                                            <th>Статус</th>
                                            <th>Target</th>
                                            <th>Saved</th>
                                            <th>Прогресс</th>
                                            <th>Payments</th>
                                            <th>Последнее пополнение</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($latestGoals as $goal)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold">{{ $goal->title }}</div>
                                                    <div class="text-muted small">Deadline: {{ optional($goal->deadline)->format('d.m.Y') ?: 'Нет' }}</div>
                                                </td>
                                                <td>{{ $goal->status }}</td>
                                                <td>{{ number_format((float) $goal->amount_total, 2, '.', ' ') }}</td>
                                                <td>{{ number_format((float) $goal->amount_saved, 2, '.', ' ') }}</td>
                                                <td>{{ number_format((float) $goal->progress, 2, '.', ' ') }}%</td>
                                                <td>{{ $goal->payments_count }}</td>
                                                <td>{{ optional($goal->latest_payment_at)->format('d.m.Y H:i') ?: 'Нет данных' }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted mb-0">Цели пользователя отсутствуют.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="card-title mb-3">Последние goal payments</h4>
                            @if($latestGoalPayments->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead class="table-light">
                                        <tr>
                                            <th>Дата</th>
                                            <th>Цель</th>
                                            <th>Сумма</th>
                                            <th>Метод</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($latestGoalPayments as $payment)
                                            <tr>
                                                <td>{{ optional($payment->created_at)->format('d.m.Y H:i') }}</td>
                                                <td>{{ $payment->goal?->title ?: 'Без цели' }}</td>
                                                <td>{{ number_format((float) $payment->amount, 2, '.', ' ') }}</td>
                                                <td>{{ $payment->method }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted mb-0">Пополнения по целям отсутствуют.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">AI insights</h4>
                    @if($latestAiInsights->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>Дата</th>
                                    <th>Тип</th>
                                    <th>Insight</th>
                                    <th>Metadata</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($latestAiInsights as $insight)
                                    @php
                                        $metadataJson = empty($insight->metadata)
                                            ? null
                                            : json_encode($insight->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                    @endphp
                                    <tr>
                                        <td>{{ optional($insight->created_at)->format('d.m.Y H:i') }}</td>
                                        <td>{{ $insight->type }}</td>
                                        <td>{{ \Illuminate\Support\Str::limit($insight->insight, 160) }}</td>
                                        <td>
                                            @if($metadataJson)
                                                <details>
                                                    <summary>{{ \Illuminate\Support\Str::limit($metadataJson, 80) }}</summary>
                                                    <pre class="small bg-light p-2 rounded mt-2 mb-0">{{ $metadataJson }}</pre>
                                                </details>
                                            @else
                                                <span class="text-muted">Нет metadata</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">AI insights для пользователя отсутствуют.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
