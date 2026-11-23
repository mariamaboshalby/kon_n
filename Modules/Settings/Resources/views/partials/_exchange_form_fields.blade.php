{{--
    Shared form fields for buy/sell currency operations.
    Required variables: $cashAccounts, $currencies, $costCenters, $branches, $newProId (create only), $exchange (edit only)
    Required variables: $accentColor ('success'|'info'), $isEdit (bool)
--}}

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- التاريخ ورقم السند --}}
<div class="row">
    <div class="col-lg-4">
        <label class="form-label">{{ __('settings::settings.date') }} <span class="text-danger">*</span></label>
        <input type="date" name="pro_date" class="form-control"
            value="{{ old('pro_date', $isEdit ? $exchange->pro_date : date('Y-m-d')) }}" required>
        @error('pro_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>
    <div class="col-lg-4">
        <label class="form-label">{{ __('settings::settings.bond_number_auto') }}</label>
        <input type="text" class="form-control"
            value="{{ $isEdit ? $exchange->pro_id : $newProId }}" readonly>
    </div>
    <div class="col-lg-4">
        <label class="form-label">{{ __('settings::settings.receipt_number') }}</label>
        <input type="text" name="pro_num" class="form-control"
            value="{{ old('pro_num', $isEdit ? $exchange->pro_num : '') }}">
    </div>
</div>

<hr>

{{-- الحسابات --}}
<div class="row">
    <div class="col-lg-6">
        <label class="form-label">{{ __('settings::settings.from_fund_credit') }} <span class="text-danger">*</span></label>
        <div class="d-flex align-items-center gap-2">
            <select name="acc2" id="acc2" class="form-control js-tom-select" required style="flex: 1;">
                <option value="">{{ __('settings::settings.select_fund') }}</option>
                @foreach ($cashAccounts as $account)
                    <option value="{{ $account->id }}"
                        data-balance="{{ $account->balance }}"
                        data-currency-id="{{ $account->currency_id }}"
                        data-currency-name="{{ $account->currency?->name ?? '' }}"
                        {{ old('acc2', $isEdit ? $exchange->acc2 : '') == $account->id ? 'selected' : '' }}>
                        {{ $account->aname }}
                    </option>
                @endforeach
            </select>
            @if (isMultiCurrencyEnabled())
                <span id="acc2_currency" class="badge bg-info text-white px-3 py-2" style="min-width: 60px; font-size: 14px;">—</span>
            @endif
        </div>
        @error('acc2') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="col-lg-6">
        <label class="form-label">{{ __('settings::settings.to_fund_debit') }} <span class="text-danger">*</span></label>
        <div class="d-flex align-items-center gap-2">
            <select name="acc1" id="acc1" class="form-control js-tom-select" required style="flex: 1;">
                <option value="">{{ __('settings::settings.select_fund') }}</option>
                @foreach ($cashAccounts as $account)
                    <option value="{{ $account->id }}"
                        data-balance="{{ $account->balance }}"
                        data-currency-id="{{ $account->currency_id }}"
                        data-currency-name="{{ $account->currency?->name ?? '' }}"
                        {{ old('acc1', $isEdit ? $exchange->acc1 : '') == $account->id ? 'selected' : '' }}>
                        {{ $account->aname }}
                    </option>
                @endforeach
            </select>
            @if (isMultiCurrencyEnabled())
                <span id="acc1_currency" class="badge bg-{{ $accentColor }} text-white px-3 py-2" style="min-width: 60px; font-size: 14px;">—</span>
            @endif
        </div>
        @error('acc1') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>
</div>

<hr>

{{-- العملة والقيمة --}}
<div class="row">
    <div class="col-lg-4">
        <label class="form-label">{{ __('settings::settings.target_currency') }} <span class="text-danger">*</span></label>
        <select name="currency_id" id="currency_id" class="form-control" required>
            <option value="">{{ __('settings::settings.select_currency') }}</option>
            @foreach ($currencies as $currency)
                <option value="{{ $currency->id }}"
                    data-rate="{{ $currency->latestRate->rate ?? 1 }}"
                    {{ old('currency_id', $isEdit ? $exchange->currency_id : '') == $currency->id ? 'selected' : '' }}>
                    {{ $currency->name }} ({{ $currency->symbol }})
                </option>
            @endforeach
        </select>
        @error('currency_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="col-lg-4">
        <label class="form-label">{{ __('settings::settings.exchange_rate') }} <span class="text-danger">*</span></label>
        <input type="number" step="0.0001" name="currency_rate" id="currency_rate" class="form-control"
            value="{{ old('currency_rate', $isEdit ? $exchange->currency_rate : 1) }}" required min="0">
        @error('currency_rate') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="col-lg-4">
        <label class="form-label">{{ __('settings::settings.amount_foreign_currency') }} <span class="text-danger">*</span></label>
        @php
            $displayValue = $isEdit
                ? ($exchange->currency_rate > 0 ? $exchange->pro_value / $exchange->currency_rate : $exchange->pro_value)
                : '';
        @endphp
        <input type="number" step="0.01" name="pro_value" id="pro_value" class="form-control"
            value="{{ old('pro_value', $isEdit ? number_format($displayValue, 2, '.', '') : '') }}" required min="0">
        @error('pro_value') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>
</div>

{{-- القيمة المحولة --}}
<div class="row mt-3">
    <div class="col-12">
        <div class="alert alert-{{ $accentColor }} d-flex align-items-center justify-content-between">
            <div>
                <i class="las la-calculator fs-4 me-2"></i>
                <strong>{{ __('settings::settings.converted_amount_local') }}:</strong>
            </div>
            <h3 class="mb-0 text-{{ $accentColor }}" id="converted_amount">
                {{ $isEdit && $exchange->pro_value ? number_format($exchange->pro_value, 2) : '0.00' }}
            </h3>
        </div>
    </div>
</div>

<hr>

{{-- البيان --}}
<div class="row">
    <div class="col-12">
        <label class="form-label">{{ __('settings::settings.description') }}</label>
        <textarea name="details" class="form-control" rows="3">{{ old('details', $isEdit ? $exchange->details : '') }}</textarea>
    </div>
</div>

<hr>

{{-- مركز التكلفة والفرع --}}
<div class="row">
    <div class="col-lg-6">
        <label class="form-label">{{ __('settings::settings.cost_center') }}</label>
        <select name="cost_center" class="form-control">
            <option value="">{{ __('settings::settings.no_cost_center') }}</option>
            @foreach ($costCenters as $cc)
                <option value="{{ $cc->id }}"
                    {{ old('cost_center', $isEdit ? $exchange->cost_center : '') == $cc->id ? 'selected' : '' }}>
                    {{ $cc->cname }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-lg-6">
        <x-branches::branch-select
            :branches="$branches"
            :selected="old('branch_id', $isEdit ? $exchange->branch_id : session('branch_id'))" />
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const proValueInput       = document.getElementById('pro_value');
    const currencyRateInput   = document.getElementById('currency_rate');
    const convertedDisplay    = document.getElementById('converted_amount');
    const currencySelect      = document.getElementById('currency_id');

    function calculateConversion() {
        const value  = parseFloat(proValueInput.value) || 0;
        const rate   = parseFloat(currencyRateInput.value) || 0;
        convertedDisplay.textContent = (value * rate).toFixed(2);
    }

    proValueInput.addEventListener('input', calculateConversion);
    currencyRateInput.addEventListener('input', calculateConversion);

    currencySelect.addEventListener('change', function () {
        const rate = this.options[this.selectedIndex]?.getAttribute('data-rate');
        if (rate) {
            currencyRateInput.value = parseFloat(rate).toFixed(4);
            calculateConversion();
        }
    });

    function updateCurrencyBadges() {
        if (!{{ isMultiCurrencyEnabled() ? 'true' : 'false' }}) return;

        ['acc1', 'acc2'].forEach(function (id) {
            const el    = document.getElementById(id);
            const badge = document.getElementById(id + '_currency');
            if (!el || !badge) return;

            const getValue = () => el.tomselect ? el.tomselect.getValue() : el.value;
            const val = getValue();
            if (val) {
                const opt = el.querySelector(`option[value="${val}"]`);
                badge.textContent = opt?.getAttribute('data-currency-name') || '—';
            }
        });
    }

    function initTomSelect() {
        if (!window.TomSelect) { setTimeout(initTomSelect, 100); return; }

        ['acc1', 'acc2'].forEach(function (id) {
            const el = document.getElementById(id);
            if (el && !el.tomselect) {
                new TomSelect(el, {
                    create: false,
                    searchField: ['text'],
                    dropdownInput: true,
                    onItemAdd: updateCurrencyBadges,
                });
            }
        });

        updateCurrencyBadges();
    }

    initTomSelect();
    calculateConversion();
});
</script>
@endpush
