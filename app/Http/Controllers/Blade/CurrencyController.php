<?php

namespace App\Http\Controllers\Blade;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Services\Check;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CurrencyController extends Controller
{
    public function index(Request $request)
    {
        Check::permission('currencies.index');

        $search = trim((string) $request->string('search'));

        $currencies = Currency::query()
            ->search($search)
            ->orderByDesc('is_default')
            ->orderByRaw('CASE WHEN sort_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->paginate(20);

        $currencies->getCollection()->transform(function (Currency $currency) {
            $currency->can_delete = !$this->isCurrencyUsed($currency);

            return $currency;
        });

        return view('pages.currencies.index', compact('currencies', 'search'));
    }

    public function create()
    {
        Check::permission('currencies.create');

        return view('pages.currencies.create', [
            'currency' => new Currency([
                'is_active' => true,
                'is_default' => false,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Check::permission('currencies.create');

        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            $currency = new Currency($data);
            $this->persistCurrency($currency, $data);
        });

        return redirect()->route('currencies.index')->with('success', 'Валюта успешно создана.');
    }

    public function edit(Currency $currency)
    {
        Check::permission('currencies.update');

        return view('pages.currencies.edit', compact('currency'));
    }

    public function update(Request $request, Currency $currency): RedirectResponse
    {
        Check::permission('currencies.update');

        $data = $this->validated($request, $currency);

        DB::transaction(function () use ($currency, $data) {
            $currency->fill($data);
            $this->persistCurrency($currency, $data);
        });

        return redirect()->route('currencies.index')->with('success', 'Валюта успешно обновлена.');
    }

    public function destroy(Currency $currency): RedirectResponse
    {
        Check::permission('currencies.delete');

        if ($currency->is_default) {
            return redirect()->back()->with('error', 'Нельзя удалить валюту по умолчанию. Сначала назначьте другую валюту по умолчанию.');
        }

        if ($this->isCurrencyUsed($currency)) {
            return redirect()->back()->with('error', 'Нельзя удалить валюту, которая уже используется в финансовых данных.');
        }

        $currency->delete();

        return redirect()->route('currencies.index')->with('success', 'Валюта успешно удалена.');
    }

    public function setDefault(Currency $currency): RedirectResponse
    {
        Check::permission('currencies.set-default');

        DB::transaction(function () use ($currency) {
            Currency::query()->where('is_default', true)->update(['is_default' => false]);

            $currency->forceFill([
                'is_default' => true,
                'is_active' => true,
            ])->save();
        });

        return redirect()->route('currencies.index')->with('success', 'Валюта по умолчанию обновлена.');
    }

    public function toggleActive(Currency $currency): RedirectResponse
    {
        Check::permission('currencies.update');

        if ($currency->is_active && $currency->is_default) {
            return redirect()->back()->with('error', 'Нельзя деактивировать валюту по умолчанию.');
        }

        if ($currency->is_active && Currency::query()->where('is_active', true)->count() <= 1) {
            return redirect()->back()->with('error', 'Нельзя деактивировать последнюю активную валюту.');
        }

        $currency->is_active = !$currency->is_active;
        $currency->save();

        return redirect()->route('currencies.index')->with('success', $currency->is_active
            ? 'Валюта активирована.'
            : 'Валюта деактивирована.');
    }

    protected function validated(Request $request, ?Currency $currency = null): array
    {
        $codeRule = 'unique:currencies,code';
        if ($currency) {
            $codeRule .= ',' . $currency->id;
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9_]+$/', $codeRule],
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $data['code'] = strtoupper(trim($data['code']));
        $data['name'] = trim($data['name']);
        $data['symbol'] = $data['symbol'] !== null ? trim((string) $data['symbol']) : null;
        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');

        if ($data['is_default']) {
            $data['is_active'] = true;
        }

        return $data;
    }

    protected function persistCurrency(Currency $currency, array $data): void
    {
        if ($data['is_default']) {
            $query = Currency::query()->where('is_default', true);

            if ($currency->exists) {
                $query->whereKeyNot($currency->id);
            }

            $query->update(['is_default' => false]);
        }

        $currency->save();

        if (!$currency->is_default && !Currency::query()->where('is_default', true)->exists()) {
            $currency->forceFill([
                'is_default' => true,
                'is_active' => true,
            ])->save();
        }
    }

    protected function isCurrencyUsed(Currency $currency): bool
    {
        $code = $currency->code;

        return DB::table('transactions')->where('currency_code', $code)->exists()
            || DB::table('budgets')->where('currency_code', $code)->exists()
            || DB::table('goals')->where('currency_code', $code)->exists()
            || DB::table('goal_payments')->where('currency_code', $code)->exists();
    }
}
