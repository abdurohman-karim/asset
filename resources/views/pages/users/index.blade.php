@extends('layouts.master')

@section('content')
    @php
        $sortDirection = function (string $column) use ($sort, $direction) {
            if ($sort !== $column) {
                return 'asc';
            }

            return $direction === 'asc' ? 'desc' : 'asc';
        };

        $sortIcon = function (string $column) use ($sort, $direction) {
            if ($sort !== $column) {
                return 'mdi mdi-swap-vertical';
            }

            return $direction === 'asc' ? 'mdi mdi-arrow-up' : 'mdi mdi-arrow-down';
        };
    @endphp

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                        <div>
                            <h4 class="card-title mb-1">Пользователи</h4>
                            <p class="text-muted mb-0">Список пользователей с ролями, активностью и основными счетчиками.</p>
                        </div>
                        @can('Создать пользователя')
                            <a href="{{ route('users.create') }}" class="btn btn-success btn-rounded waves-effect waves-light">
                                <i class="fas fa-user-plus align-middle font-size-16"></i> Добавить пользователя
                            </a>
                        @endcan
                    </div>

                    <x-alert-success/>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4 col-xl-2">
                            <div class="border rounded p-3 h-100">
                                <p class="text-muted mb-1">Всего</p>
                                <h4 class="mb-0">{{ $stats['total_users'] }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4 col-xl-2">
                            <div class="border rounded p-3 h-100">
                                <p class="text-muted mb-1">Админы</p>
                                <h4 class="mb-0">{{ $stats['admin_users'] }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4 col-xl-2">
                            <div class="border rounded p-3 h-100">
                                <p class="text-muted mb-1">Super Admin</p>
                                <h4 class="mb-0">{{ $stats['super_admins'] }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4 col-xl-2">
                            <div class="border rounded p-3 h-100">
                                <p class="text-muted mb-1">С транзакциями</p>
                                <h4 class="mb-0">{{ $stats['users_with_transactions'] }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4 col-xl-2">
                            <div class="border rounded p-3 h-100">
                                <p class="text-muted mb-1">С целями</p>
                                <h4 class="mb-0">{{ $stats['users_with_goals'] }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4 col-xl-2">
                            <div class="border rounded p-3 h-100">
                                <p class="text-muted mb-1">С AI insights</p>
                                <h4 class="mb-0">{{ $stats['users_with_ai_insights'] }}</h4>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('users.index') }}" method="get" class="row g-3 mb-4">
                        <div class="col-lg-6">
                            <label for="search" class="form-label">Поиск</label>
                            <input
                                type="text"
                                id="search"
                                name="search"
                                class="form-control"
                                value="{{ $search }}"
                                placeholder="Имя, email, телефон или Telegram ID"
                            >
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <label for="sort" class="form-label">Сортировка</label>
                            <select name="sort" id="sort" class="form-select">
                                <option value="id" @selected($sort === 'id')>ID</option>
                                <option value="name" @selected($sort === 'name')>Имя</option>
                                <option value="email" @selected($sort === 'email')>Email</option>
                                <option value="phone" @selected($sort === 'phone')>Телефон</option>
                                <option value="created_at" @selected($sort === 'created_at')>Дата создания</option>
                                <option value="transactions_count" @selected($sort === 'transactions_count')>Транзакции</option>
                                <option value="goals_count" @selected($sort === 'goals_count')>Цели</option>
                                <option value="last_transaction_at" @selected($sort === 'last_transaction_at')>Последняя транзакция</option>
                                <option value="last_ai_insight_at" @selected($sort === 'last_ai_insight_at')>Последний AI insight</option>
                            </select>
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <label for="direction" class="form-label">Направление</label>
                            <select name="direction" id="direction" class="form-select">
                                <option value="desc" @selected($direction === 'desc')>По убыванию</option>
                                <option value="asc" @selected($direction === 'asc')>По возрастанию</option>
                            </select>
                        </div>
                        <div class="col-lg-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary w-100">Применить</button>
                            @if(request()->hasAny(['search', 'sort', 'direction']))
                                <a href="{{ route('users.index') }}" class="btn btn-light w-100">Сбросить</a>
                            @endif
                        </div>
                    </form>

                    @if($users->count())
                        <div class="table-responsive">
                            <table class="table align-middle table-nowrap">
                                <thead class="table-light">
                                <tr>
                                    <th>
                                        <a class="text-dark d-inline-flex align-items-center gap-1" href="{{ route('users.index', array_merge(request()->query(), ['sort' => 'id', 'direction' => $sortDirection('id')])) }}">
                                            ID
                                            <i class="{{ $sortIcon('id') }}"></i>
                                        </a>
                                    </th>
                                    <th>
                                        <a class="text-dark d-inline-flex align-items-center gap-1" href="{{ route('users.index', array_merge(request()->query(), ['sort' => 'name', 'direction' => $sortDirection('name')])) }}">
                                            Пользователь
                                            <i class="{{ $sortIcon('name') }}"></i>
                                        </a>
                                    </th>
                                    <th>Контакты</th>
                                    <th>Роли</th>
                                    <th>
                                        <a class="text-dark d-inline-flex align-items-center gap-1" href="{{ route('users.index', array_merge(request()->query(), ['sort' => 'created_at', 'direction' => $sortDirection('created_at')])) }}">
                                            Создан
                                            <i class="{{ $sortIcon('created_at') }}"></i>
                                        </a>
                                    </th>
                                    <th>Активность</th>
                                    <th>Счетчики</th>
                                    <th>Статус</th>
                                    <th class="text-center">Действия</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($users as $user)
                                    <tr>
                                        <td class="fw-semibold">{{ $user->id }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $user->name }}</div>
                                            @if($user->language)
                                                <div class="text-muted small">Язык: {{ strtoupper($user->language) }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div>{{ $user->email ?: 'Нет email' }}</div>
                                            <div class="text-muted small">{{ $user->phone ?: 'Нет телефона' }}</div>
                                            <div class="text-muted small">TG: {{ $user->tg_user_id ?: 'Не указан' }}</div>
                                        </td>
                                        <td>
                                            @forelse($user->roles as $role)
                                                <span class="badge badge-soft-primary font-size-11 m-1">{{ $role->name }}</span>
                                            @empty
                                                <span class="text-muted">Нет ролей</span>
                                            @endforelse
                                        </td>
                                        <td>
                                            <div>{{ optional($user->created_at)->format('d.m.Y H:i') }}</div>
                                            <div class="text-muted small">Обновлен: {{ optional($user->updated_at)->format('d.m.Y H:i') }}</div>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <span class="text-muted">Транзакция:</span>
                                                {{ optional($user->last_transaction_at)->format('d.m.Y H:i') ?: 'Нет данных' }}
                                            </div>
                                            <div class="small">
                                                <span class="text-muted">Goal payment:</span>
                                                {{ optional($user->last_goal_payment_at)->format('d.m.Y H:i') ?: 'Нет данных' }}
                                            </div>
                                            <div class="small">
                                                <span class="text-muted">AI insight:</span>
                                                {{ optional($user->last_ai_insight_at)->format('d.m.Y H:i') ?: 'Нет данных' }}
                                            </div>
                                        </td>
                                        <td class="small">
                                            <div>Транзакции: {{ $user->transactions_count }}</div>
                                            <div>Бюджеты: {{ $user->budgets_count }}</div>
                                            <div>Цели: {{ $user->goals_count }}</div>
                                            <div>Goal payments: {{ $user->goal_payments_count }}</div>
                                            <div>AI insights: {{ $user->ai_insights_count }}</div>
                                        </td>
                                        <td>
                                            @php($isSuperAdmin = $user->roles->contains('name', 'Super Admin'))
                                            @if($user->is_admin)
                                                <span class="badge badge-soft-success font-size-11 mb-1">Администратор</span>
                                            @endif
                                            @if($isSuperAdmin)
                                                <span class="badge badge-soft-danger font-size-11 mb-1">Super Admin</span>
                                            @endif
                                            @if(!$user->is_admin && !$isSuperAdmin)
                                                <span class="badge badge-soft-secondary font-size-11">Пользователь</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="{{ route('users.show', $user) }}" class="btn btn-outline-primary btn-sm">
                                                    <i class="far fa-eye"></i>
                                                </a>
                                                <a href="{{ route('users.edit', $user) }}" class="btn btn-outline-success btn-sm">
                                                    <i class="fas fa-user-edit"></i>
                                                </a>
                                                @can('Удалить пользователя')
                                                    <form action="{{ route('users.destroy', $user) }}" method="post">
                                                        @csrf
                                                        @method('delete')
                                                        <button type="button" class="submitButtonConfirm btn btn-outline-danger btn-sm">
                                                            <i class="fas fa-user-times"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <h5 class="mb-2">Пользователи не найдены</h5>
                            <p class="text-muted mb-0">По текущему фильтру нет данных. Измените поиск или дождитесь появления пользователей в базе.</p>
                        </div>
                    @endif

                    {{ $users->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
