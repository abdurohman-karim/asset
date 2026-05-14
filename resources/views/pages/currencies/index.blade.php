@extends('layouts.master')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                        <div>
                            <h4 class="card-title mb-1">Валюты</h4>
                            <p class="text-muted mb-0">Управление списком валют для бота, RPC и финансовых записей.</p>
                        </div>
                        @can('currencies.create')
                            <a href="{{ route('currencies.create') }}" class="btn btn-success btn-rounded waves-effect waves-light">
                                <i class="fa fa-plus align-middle font-size-16"></i> Добавить валюту
                            </a>
                        @endcan
                    </div>

                    <x-alert-success />

                    <form action="{{ route('currencies.index') }}" method="get" class="row g-3 mb-4">
                        <div class="col-lg-8">
                            <label for="search" class="form-label">Поиск</label>
                            <input
                                type="text"
                                id="search"
                                name="search"
                                class="form-control"
                                value="{{ $search }}"
                                placeholder="Код, название или символ"
                            >
                        </div>
                        <div class="col-lg-4 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary w-100">Применить</button>
                            @if($search !== '')
                                <a href="{{ route('currencies.index') }}" class="btn btn-light w-100">Сбросить</a>
                            @endif
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap">
                            <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Код</th>
                                <th>Название</th>
                                <th>Символ</th>
                                <th>Статус</th>
                                <th>По умолчанию</th>
                                <th>Сортировка</th>
                                <th>Создана</th>
                                <th class="text-center">Действия</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($currencies as $currency)
                                <tr>
                                    <td>{{ $currency->id }}</td>
                                    <td><span class="fw-semibold">{{ strtoupper($currency->code) }}</span></td>
                                    <td>{{ $currency->name }}</td>
                                    <td>{{ $currency->symbol ?: '—' }}</td>
                                    <td>
                                        @if($currency->is_active)
                                            <span class="badge badge-soft-success font-size-11">Активна</span>
                                        @else
                                            <span class="badge badge-soft-secondary font-size-11">Неактивна</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($currency->is_default)
                                            <span class="badge badge-soft-primary font-size-11">Да</span>
                                        @else
                                            <span class="text-muted">Нет</span>
                                        @endif
                                    </td>
                                    <td>{{ $currency->sort_order ?? '—' }}</td>
                                    <td>{{ optional($currency->created_at)->format('d.m.Y H:i') ?: '—' }}</td>
                                    <td class="text-center">
                                        <div class="d-flex flex-wrap justify-content-center gap-2">
                                            @can('currencies.update')
                                                <a href="{{ route('currencies.edit', $currency) }}" class="btn btn-outline-success btn-sm">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                            @endcan

                                            @can('currencies.update')
                                                <form action="{{ route('currencies.toggle-active', $currency) }}" method="post">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-warning btn-sm">
                                                        @if($currency->is_active)
                                                            <i class="fas fa-pause"></i>
                                                        @else
                                                            <i class="fas fa-play"></i>
                                                        @endif
                                                    </button>
                                                </form>
                                            @endcan

                                            @can('currencies.set-default')
                                                @unless($currency->is_default)
                                                    <form action="{{ route('currencies.set-default', $currency) }}" method="post">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-star"></i>
                                                        </button>
                                                    </form>
                                                @endunless
                                            @endcan

                                            @can('currencies.delete')
                                                <form action="{{ route('currencies.destroy', $currency) }}" method="post">
                                                    @csrf
                                                    @method('delete')
                                                    <button
                                                        type="button"
                                                        class="submitButtonConfirm btn btn-outline-danger btn-sm"
                                                        @disabled(!$currency->can_delete || $currency->is_default)
                                                        title="{{ !$currency->can_delete ? 'Валюта уже используется' : ($currency->is_default ? 'Нельзя удалить валюту по умолчанию' : '') }}"
                                                    >
                                                        <i class="far fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        Валюты не найдены.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $currencies->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
