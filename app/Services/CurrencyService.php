<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CurrencyService
{
    protected ?bool $currencyTableExists = null;

    protected ?array $currencyColumns = null;

    protected ?array $allCurrencies = null;

    protected ?array $activeCurrencies = null;

    public function listActive(): array
    {
        return array_map(
            fn (array $currency) => $this->normalizeCurrencyRow($currency),
            $this->loadCurrencies(activeOnly: true)
        );
    }

    public function listAll(): array
    {
        return array_map(
            fn (array $currency) => $this->normalizeCurrencyRow($currency),
            $this->loadCurrencies(activeOnly: false)
        );
    }

    public function defaultCurrency(): array
    {
        $default = collect($this->listActive())->firstWhere('is_default', true);

        if ($default) {
            return $default;
        }

        return $this->listActive()[0] ?? $this->fallbackCurrency();
    }

    public function preferredCurrency(?User $user): array
    {
        if (!$user) {
            return $this->defaultCurrency();
        }

        $settings = $user->settings ?? [];

        $code = $this->normalizeCode((string) Arr::get($settings, 'currency_code', ''));
        if ($code) {
            $currency = $this->findActiveByCode($code);
            if ($currency) {
                return $currency;
            }
        }

        $id = Arr::get($settings, 'currency_id');
        if ($id !== null) {
            $currency = $this->findActiveById($id);
            if ($currency) {
                return $currency;
            }
        }

        return $this->defaultCurrency();
    }

    public function preferredCurrencyCode(?User $user): string
    {
        return $this->preferredCurrency($user)['code'];
    }

    public function setPreferredCurrency(User $user, array|string $currency): array
    {
        $resolved = is_array($currency)
            ? $this->normalizeCurrencyRow($currency)
            : ($this->findActiveByCode((string) $currency) ?? $this->defaultCurrency());

        $settings = $user->settings ?? [];
        $settings['currency_id'] = $resolved['id'];
        $settings['currency_code'] = $resolved['code'];

        $user->settings = $settings;
        $user->save();

        return $resolved;
    }

    public function resolveSelection(array $params, ?User $user = null): array
    {
        $currencyId = Arr::get($params, 'currency_id');
        $currencyCode = $this->normalizeCode((string) Arr::get($params, 'currency_code', ''));

        if ($currencyId !== null) {
            $currency = $this->findActiveById($currencyId);
            if (!$currency) {
                throw new \RuntimeException('Currency not found or inactive');
            }

            return $currency;
        }

        if ($currencyCode !== null) {
            $currency = $this->findActiveByCode($currencyCode);
            if (!$currency) {
                throw new \RuntimeException('Currency not found or inactive');
            }

            return $currency;
        }

        return $this->preferredCurrency($user);
    }

    public function currencyForStoredCode(?string $currencyCode): array
    {
        $normalized = $this->normalizeCode((string) $currencyCode);
        if (!$normalized) {
            return $this->defaultCurrency();
        }

        $currency = $this->findAnyByCode($normalized);
        if ($currency) {
            return $currency;
        }

        $fallback = $this->fallbackCurrency();
        $fallback['code'] = $normalized;
        $fallback['name'] = $normalized;
        $fallback['symbol'] = $normalized;
        $fallback['is_default'] = false;

        return $fallback;
    }

    public function serialize(array $currency): array
    {
        $normalized = $this->normalizeCurrencyRow($currency);

        return [
            'id' => $normalized['id'],
            'code' => $normalized['code'],
            'name' => $normalized['name'],
            'symbol' => $normalized['symbol'],
            'is_default' => $normalized['is_default'],
        ];
    }

    public function formatAmount(int|float|string|null $amount, ?array $currency = null, ?string $sign = null): string
    {
        if (!is_numeric($amount)) {
            return '—';
        }

        $currency = $currency ? $this->normalizeCurrencyRow($currency) : $this->defaultCurrency();

        $number = (float) $amount;
        $negative = $number < 0;
        $number = abs($number);

        $decimals = (int) $currency['decimals'];
        $thousandsSeparator = $currency['symbol_position'] === 'prefix' ? ',' : ' ';
        $formatted = number_format($number, $decimals, '.', $thousandsSeparator);

        if ($decimals === 0) {
            $formatted = preg_replace('/\.0+$/', '', $formatted) ?: $formatted;
        }

        $effectiveSign = $sign;
        if ($effectiveSign === null && $negative) {
            $effectiveSign = '-';
        }

        if ($effectiveSign) {
            $formatted = $effectiveSign . $formatted;
        }

        $symbol = $currency['symbol'] ?: $currency['code'];

        return $currency['symbol_position'] === 'prefix'
            ? $symbol . $formatted
            : trim($formatted . ' ' . $symbol);
    }

    public function hasCurrencyTable(): bool
    {
        if ($this->currencyTableExists !== null) {
            return $this->currencyTableExists;
        }

        try {
            return $this->currencyTableExists = Schema::hasTable('currencies');
        } catch (\Throwable) {
            return $this->currencyTableExists = false;
        }
    }

    protected function findActiveById(mixed $id): ?array
    {
        return collect($this->listActive())->firstWhere('id', $id);
    }

    protected function findActiveByCode(string $code): ?array
    {
        return collect($this->listActive())->firstWhere('code', $this->normalizeCode($code));
    }

    protected function findAnyByCode(string $code): ?array
    {
        return collect($this->listAll())->firstWhere('code', $this->normalizeCode($code));
    }

    protected function loadCurrencies(bool $activeOnly): array
    {
        if (!$this->hasCurrencyTable()) {
            return [$this->fallbackCurrency()];
        }

        $cache = $activeOnly ? $this->activeCurrencies : $this->allCurrencies;
        if ($cache !== null) {
            return $cache;
        }

        $columns = $this->currencyColumns();
        $query = DB::table('currencies');

        if ($activeOnly && isset($columns['is_active'])) {
            $query->where($columns['is_active'], true);
        }

        if (isset($columns['is_default'])) {
            $query->orderByDesc($columns['is_default']);
        }

        if (isset($columns['sort_order'])) {
            $query->orderByRaw("CASE WHEN {$columns['sort_order']} IS NULL THEN 1 ELSE 0 END");
            $query->orderBy($columns['sort_order']);
        }

        if (isset($columns['code'])) {
            $query->orderBy($columns['code']);
        } elseif (isset($columns['name'])) {
            $query->orderBy($columns['name']);
        } else {
            $query->orderBy('id');
        }

        $rows = $query->get()->map(function (object $row) use ($columns) {
            return [
                'id' => $columns['id'] ? data_get($row, $columns['id']) : null,
                'code' => $this->normalizeCode((string) data_get($row, $columns['code'] ?? 'code')),
                'name' => (string) data_get($row, $columns['name'] ?? 'name'),
                'symbol' => (string) data_get($row, $columns['symbol'] ?? 'symbol'),
                'is_active' => $columns['is_active']
                    ? (bool) data_get($row, $columns['is_active'])
                    : true,
                'is_default' => $columns['is_default']
                    ? (bool) data_get($row, $columns['is_default'])
                    : false,
                'decimals' => $columns['decimals']
                    ? (int) data_get($row, $columns['decimals'])
                    : null,
                'symbol_position' => $columns['symbol_position']
                    ? (string) data_get($row, $columns['symbol_position'])
                    : null,
            ];
        })->filter(fn (array $currency) => !empty($currency['code']))
            ->map(fn (array $currency) => $this->normalizeCurrencyRow($currency))
            ->values()
            ->all();

        if (empty($rows)) {
            $rows = [$this->fallbackCurrency()];
        }

        if ($activeOnly) {
            $this->activeCurrencies = $rows;
        } else {
            $this->allCurrencies = $rows;
        }

        return $rows;
    }

    protected function currencyColumns(): array
    {
        if ($this->currencyColumns !== null) {
            return $this->currencyColumns;
        }

        $columns = Schema::getColumnListing('currencies');

        $this->currencyColumns = [
            'id' => in_array('id', $columns, true) ? 'id' : null,
            'code' => $this->firstExisting($columns, ['code', 'currency_code']),
            'name' => $this->firstExisting($columns, ['name', 'title']),
            'symbol' => $this->firstExisting($columns, ['symbol', 'sign']),
            'is_active' => $this->firstExisting($columns, ['is_active', 'active']),
            'is_default' => $this->firstExisting($columns, ['is_default', 'default']),
            'sort_order' => $this->firstExisting($columns, ['sort_order']),
            'decimals' => $this->firstExisting($columns, ['decimals', 'precision']),
            'symbol_position' => $this->firstExisting($columns, ['symbol_position']),
        ];

        return $this->currencyColumns;
    }

    protected function firstExisting(array $columns, array $candidates): ?string
    {
        return Collection::make($candidates)->first(fn (string $candidate) => in_array($candidate, $columns, true));
    }

    protected function normalizeCurrencyRow(array $currency): array
    {
        $code = $this->normalizeCode((string) ($currency['code'] ?? '')) ?: config('currency.default_code', 'UZS');
        $symbol = (string) ($currency['symbol'] ?? '');
        $name = trim((string) ($currency['name'] ?? $code));
        $position = (string) ($currency['symbol_position'] ?? '');

        if ($symbol === '' && $code === 'UZS') {
            $symbol = 'сум';
        }

        if ($position !== 'prefix' && $position !== 'suffix') {
            $position = in_array($symbol, ['$', '€', '£', '¥'], true) ? 'prefix' : 'suffix';
        }

        $decimals = $currency['decimals'] ?? null;
        if ($decimals === null) {
            $decimals = $code === 'UZS' ? 0 : 2;
        }

        return [
            'id' => $currency['id'] ?? null,
            'code' => $code,
            'name' => $name !== '' ? $name : $code,
            'symbol' => $symbol !== '' ? $symbol : $code,
            'is_active' => (bool) ($currency['is_active'] ?? true),
            'is_default' => (bool) ($currency['is_default'] ?? false),
            'decimals' => (int) $decimals,
            'symbol_position' => $position,
        ];
    }

    protected function fallbackCurrency(): array
    {
        return [
            'id' => null,
            'code' => config('currency.default_code', 'UZS'),
            'name' => 'Uzbekistan Som',
            'symbol' => 'сум',
            'is_active' => true,
            'is_default' => true,
            'decimals' => 0,
            'symbol_position' => 'suffix',
        ];
    }

    protected function normalizeCode(string $code): ?string
    {
        $normalized = strtoupper(trim($code));

        return $normalized !== '' ? $normalized : null;
    }
}
