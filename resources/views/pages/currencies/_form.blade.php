@csrf
@if($isEdit)
    @method('put')
@endif

<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="mb-3">
            <label for="code" class="form-label">Код</label>
            <input
                type="text"
                id="code"
                name="code"
                class="form-control @error('code') is-invalid @enderror"
                value="{{ old('code', $currency->code) }}"
                maxlength="10"
                placeholder="UZS"
                required
            >
            @error('code')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="mb-3">
            <label for="name" class="form-label">Название</label>
            <input
                type="text"
                id="name"
                name="name"
                class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name', $currency->name) }}"
                maxlength="255"
                placeholder="Uzbekistani Som"
                required
            >
            @error('name')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="mb-3">
            <label for="symbol" class="form-label">Символ</label>
            <input
                type="text"
                id="symbol"
                name="symbol"
                class="form-control @error('symbol') is-invalid @enderror"
                value="{{ old('symbol', $currency->symbol) }}"
                maxlength="20"
                placeholder="сум"
            >
            @error('symbol')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="mb-3">
            <label for="sort_order" class="form-label">Порядок сортировки</label>
            <input
                type="number"
                id="sort_order"
                name="sort_order"
                class="form-control @error('sort_order') is-invalid @enderror"
                value="{{ old('sort_order', $currency->sort_order) }}"
                placeholder="1"
            >
            @error('sort_order')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="form-check form-switch mb-3">
            <input
                class="form-check-input"
                type="checkbox"
                id="is_active"
                name="is_active"
                value="1"
                @checked(old('is_active', $currency->is_active))
            >
            <label class="form-check-label" for="is_active">Активна</label>
        </div>
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="form-check form-switch mb-3">
            <input
                class="form-check-input"
                type="checkbox"
                id="is_default"
                name="is_default"
                value="1"
                @checked(old('is_default', $currency->is_default))
            >
            <label class="form-check-label" for="is_default">По умолчанию</label>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="text-lg-end text-sm-center">
        <button type="submit" class="btn btn-success w-md waves-effect waves-light">
            <i class="fas fa-save"></i> {{ $submitLabel }}
        </button>
        <a href="{{ route('currencies.index') }}" class="btn btn-secondary w-md">Отмена</a>
    </div>
</div>
