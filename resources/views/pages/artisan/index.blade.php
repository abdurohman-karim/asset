@extends('layouts.master')

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8 col-lg-10">

        {{-- Page header --}}
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <div>
                <h4 class="mb-1">Artisan Console</h4>
                <p class="text-muted mb-0">Запуск разрешённых Artisan команд из админ панели</p>
            </div>
            <span class="badge bg-soft-danger text-danger border border-danger px-3 py-2">
                <i class="mdi mdi-shield-lock me-1"></i>Только Super Admin
            </span>
        </div>

        {{-- Command form --}}
        <div class="card">
            <div class="card-body">

                <div class="mb-3">
                    <label for="command" class="form-label fw-semibold">Команда</label>
                    <input
                        type="text"
                        name="command"
                        id="command"
                        class="form-control form-control-lg font-monospace"
                        placeholder="Например: optimize:clear"
                        autocomplete="off"
                        spellcheck="false"
                    >
                </div>

                <div class="mb-4">
                    <label for="arguments-input" class="form-label fw-semibold">
                        Аргументы / опции
                        <span class="text-muted fw-normal">(необязательно)</span>
                    </label>
                    <input
                        type="text"
                        id="arguments-input"
                        class="form-control font-monospace"
                        placeholder="Например: --queue=emails --tries=3"
                        autocomplete="off"
                        spellcheck="false"
                    >
                    <div class="form-text">Принимаются только флаги <code>--ключ</code> и <code>--ключ=значение</code></div>
                </div>

                <div id="risky-warning" class="alert alert-warning align-items-center gap-2 mb-4 d-none" role="alert">
                    <i class="mdi mdi-alert font-size-16"></i>
                    Эта команда помечена как <strong>опасная</strong> — потребуется подтверждение.
                </div>

                <button id="run-btn" class="btn btn-primary waves-effect waves-light px-4">
                    <span id="btn-text"><i class="mdi mdi-play me-1"></i>Запустить</span>
                    <span id="btn-spinner" class="d-none">
                        <span class="spinner-border spinner-border-sm me-1" role="status"></span>Выполняется…
                    </span>
                </button>
            </div>
        </div>

        {{-- Output --}}
        <div id="output-card" class="card d-none">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <h5 class="card-title mb-0">Результат выполнения</h5>
                    <div class="d-flex align-items-center gap-3">
                        <span id="output-status-badge"></span>
                        <span id="output-time" class="text-muted" style="font-size:0.8rem"></span>
                        <button id="copy-btn" class="btn btn-sm btn-light" title="Скопировать вывод">
                            <i class="mdi mdi-content-copy"></i>
                        </button>
                    </div>
                </div>
                <pre id="output-pre"
                     style="background:#1e1e1e;color:#d4d4d4;padding:16px;border-radius:6px;font-size:0.82rem;min-height:80px;max-height:420px;overflow-y:auto;white-space:pre-wrap;word-break:break-all;margin:0;"></pre>
            </div>
        </div>

        {{-- History --}}
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Последние запуски</h5>

                <p id="no-logs-msg" class="text-muted mb-0 {{ $logs->isNotEmpty() ? 'd-none' : '' }}">
                    Команды ещё не запускались.
                </p>

                <div id="log-wrapper" class="table-responsive {{ $logs->isEmpty() ? 'd-none' : '' }}">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Команда</th>
                                <th>Опции</th>
                                <th>Статус</th>
                                <th>Время (с)</th>
                                <th>Пользователь</th>
                                <th>IP</th>
                                <th>Дата</th>
                            </tr>
                        </thead>
                        <tbody id="log-tbody">
                            @foreach($logs as $log)
                                <tr>
                                    <td><code>{{ $log->command }}</code></td>
                                    <td>
                                        @if($log->parameters)
                                            <code class="text-muted" style="font-size:0.78rem">
                                                {{ collect($log->parameters)->map(fn($v,$k) => $v === true ? $k : "$k=$v")->implode(' ') }}
                                            </code>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->status === 'success')
                                            <span class="badge bg-success">успех</span>
                                        @else
                                            <span class="badge bg-danger">ошибка</span>
                                        @endif
                                    </td>
                                    <td>{{ $log->execution_time }}</td>
                                    <td>{{ $log->user?->name ?? '—' }}</td>
                                    <td class="text-muted" style="font-size:0.78rem">{{ $log->ip_address }}</td>
                                    <td class="text-muted text-nowrap" style="font-size:0.78rem">
                                        {{ $log->created_at->format('d.m.Y H:i:s') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Confirmation modal --}}
<div class="modal fade" id="confirm-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title">
                    <i class="mdi mdi-alert-circle text-warning me-2"></i>Подтверждение
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0">
                <p class="text-muted mb-2">Вы собираетесь выполнить:</p>
                <p class="mb-3">
                    <code id="modal-cmd" class="fs-6"></code>
                </p>
                <div class="alert alert-warning mb-0">
                    <i class="mdi mdi-alert me-1"></i>
                    Эта команда может очистить кеш или повлиять на рабочие процессы. Продолжить?
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" id="modal-confirm-btn">
                    <i class="mdi mdi-play me-1"></i>Да, выполнить
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
(function () {
    'use strict';

    const commandInput = document.getElementById('command');
    const argsInput    = document.getElementById('arguments-input');
    const runBtn       = document.getElementById('run-btn');
    const btnText      = document.getElementById('btn-text');
    const btnSpinner   = document.getElementById('btn-spinner');
    const outputCard   = document.getElementById('output-card');
    const outputPre    = document.getElementById('output-pre');
    const statusBadge  = document.getElementById('output-status-badge');
    const outputTime   = document.getElementById('output-time');
    const copyBtn      = document.getElementById('copy-btn');
    const riskyWarn    = document.getElementById('risky-warning');
    const modalEl      = document.getElementById('confirm-modal');
    const modalCmd     = document.getElementById('modal-cmd');
    const modalConfirm = document.getElementById('modal-confirm-btn');
    const confirmModal = new bootstrap.Modal(modalEl);

    const CSRF    = '{{ csrf_token() }}';
    const RUN_URL = '{{ route('artisan-console.run') }}';
    const RISKY   = @json(array_keys(array_filter($commands, fn($m) => $m['risky'])));
    const ALLOWED = @json(array_keys($commands));

    // Update risky warning as the user types
    commandInput.addEventListener('input', function () {
        const cmd = commandInput.value.trim();
        riskyWarn.classList.toggle('d-none', !RISKY.includes(cmd));
        riskyWarn.classList.toggle('d-flex', RISKY.includes(cmd));
    });

    // Run on button click
    runBtn.addEventListener('click', handleRun);

    // Run on Enter in either input
    [commandInput, argsInput].forEach(function (el) {
        el.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); handleRun(); }
        });
    });

    // Confirm modal → execute
    modalConfirm.addEventListener('click', function () {
        confirmModal.hide();
        executeCommand(commandInput.value.trim(), argsInput.value.trim());
    });

    // Copy output
    copyBtn.addEventListener('click', function () {
        navigator.clipboard.writeText(outputPre.textContent).then(function () {
            toastr.success('Вывод скопирован в буфер обмена');
        });
    });

    function handleRun() {
        const cmd = commandInput.value.trim();
        if (!cmd) { commandInput.focus(); return; }

        if (!ALLOWED.includes(cmd)) {
            showOutput('Ошибка: команда «' + escHtml(cmd) + '» не входит в список разрешённых.', 'error', null);
            return;
        }

        if (RISKY.includes(cmd)) {
            modalCmd.textContent = cmd + (argsInput.value.trim() ? '  ' + argsInput.value.trim() : '');
            confirmModal.show();
        } else {
            executeCommand(cmd, argsInput.value.trim());
        }
    }

    function executeCommand(cmd, args) {
        setLoading(true);
        showOutput('Выполняется…', null, null);

        fetch(RUN_URL, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Content-Type': 'application/json',
                'Accept':       'application/json',
            },
            body: JSON.stringify({ command: cmd, arguments: args }),
        })
        .then(function (res) {
            if (res.status === 429) {
                throw new Error('Слишком много запросов. Подождите немного.');
            }
            return res.json().then(function (data) {
                if (!res.ok) throw new Error(data.error || data.message || 'Ошибка сервера.');
                return data;
            });
        })
        .then(function (data) {
            showOutput(data.output, data.status, data.execution_time);
            prependLogRow(cmd, args, data);
        })
        .catch(function (err) {
            showOutput(err.message, 'error', null);
            toastr.error(err.message);
        })
        .finally(function () {
            setLoading(false);
        });
    }

    function showOutput(text, status, time) {
        outputCard.classList.remove('d-none');
        outputPre.textContent = text;

        if (status === 'success') {
            statusBadge.innerHTML = '<span class="badge bg-success">успех</span>';
        } else if (status === 'error') {
            statusBadge.innerHTML = '<span class="badge bg-danger">ошибка</span>';
        } else {
            statusBadge.innerHTML = '';
        }

        outputTime.textContent = time !== null ? time + ' с' : '';
        outputCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function setLoading(loading) {
        runBtn.disabled = loading;
        btnText.classList.toggle('d-none', loading);
        btnSpinner.classList.toggle('d-none', !loading);
    }

    function prependLogRow(cmd, args, data) {
        document.getElementById('no-logs-msg').classList.add('d-none');
        document.getElementById('log-wrapper').classList.remove('d-none');

        const badge = data.status === 'success'
            ? '<span class="badge bg-success">успех</span>'
            : '<span class="badge bg-danger">ошибка</span>';

        const now = new Date();
        const p   = n => String(n).padStart(2, '0');
        const date = p(now.getDate()) + '.' + p(now.getMonth() + 1) + '.' + now.getFullYear()
                   + ' ' + p(now.getHours()) + ':' + p(now.getMinutes()) + ':' + p(now.getSeconds());

        const row = document.createElement('tr');
        row.innerHTML =
            '<td><code>' + escHtml(cmd) + '</code></td>' +
            '<td><code style="font-size:0.78rem;color:#888">' + escHtml(args) + '</code></td>' +
            '<td>' + badge + '</td>' +
            '<td>' + (data.execution_time ?? '—') + '</td>' +
            '<td>{{ auth()->user()->name }}</td>' +
            '<td style="font-size:0.78rem;color:#888">{{ request()->ip() }}</td>' +
            '<td style="font-size:0.78rem;color:#888" class="text-nowrap">' + date + '</td>';

        document.getElementById('log-tbody').prepend(row);
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
}());
</script>
@endsection
