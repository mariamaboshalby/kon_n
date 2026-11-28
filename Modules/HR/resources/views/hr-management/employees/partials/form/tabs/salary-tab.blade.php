@php
    $p = $p ?? [];
    $currencies = $currencies ?? \Modules\Settings\Models\Currency::active()->with('latestRate')->orderBy('is_default', 'desc')->orderBy('name')->get();
    $defaultCurrency = $defaultCurrency ?? \Modules\Settings\Models\Currency::default()->first() ?? (object)['id' => 1, 'code' => 'AED', 'symbol' => 'د.إ', 'name' => 'Default'];
    
    $selectedCurrencyId = old('currency_id', $p['currency_id'] ?? $defaultCurrency->id);
    $selectedCurrency = $currencies->firstWhere('id', $selectedCurrencyId) ?? $defaultCurrency;
    
    $initialExchangeRate = old('exchange_rate', $p['exchange_rate'] ?? ($selectedCurrency->is_default ? 1.0 : ($selectedCurrency->latestRate->rate ?? 1.0)));
    if (empty($initialExchangeRate) || (float)$initialExchangeRate <= 0) {
        $initialExchangeRate = 1.0;
    }

    $currencySalary = old('currency_salary', $p['currency_salary'] ?? ($p['salary'] ?? ''));
    $baseSalary = old('salary', $p['salary'] ?? '');
    if ($currencySalary !== '' && is_numeric($currencySalary) && is_numeric($initialExchangeRate)) {
        $baseSalary = round(((float)$currencySalary) * ((float)$initialExchangeRate), 2);
    }
@endphp

<div>
    <div class="row g-3">
        {{-- كرت الراتب والعملة ومعامل التحويل --}}
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-gradient-success text-white py-2 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 font-hold fw-bold">
                        <i class="fas fa-money-bill-wave me-2"></i>{{ __('hr::hr.salary') }} وعملة المعاملة
                    </h6>
                    <span class="badge bg-light text-dark font-monospace">
                        العملة الأساسية: {{ $defaultCurrency->name ?? $defaultCurrency->code }} ({{ $defaultCurrency->symbol ?? $defaultCurrency->code }})
                    </span>
                </div>
                <div class="card-body py-3">
                    <div class="row g-3 mb-3">
                        {{-- نوع العملة --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                {{ __('عملة الراتب / الحركة') }}
                                <span class="badge bg-secondary-subtle text-secondary ms-1 fw-normal" style="font-size: 0.75rem;">اختيارية</span>
                            </label>
                            <select name="currency_id" id="emp_currency_id" class="form-select select2">
                                @foreach ($currencies as $cur)
                                    @php
                                        $curRate = $cur->is_default ? 1.0 : ($cur->latestRate->rate ?? 1.0);
                                    @endphp
                                    <option value="{{ $cur->id }}"
                                            data-code="{{ $cur->code }}"
                                            data-symbol="{{ $cur->symbol ?? $cur->code }}"
                                            data-is-default="{{ $cur->is_default ? '1' : '0' }}"
                                            data-rate="{{ $curRate }}"
                                            data-live-url="{{ route('currencies.fetch-live-rate', $cur->id) }}"
                                            @selected((string)$selectedCurrencyId === (string)$cur->id)>
                                        {{ $cur->code }} - {{ $cur->name }} ({{ $cur->symbol ?? $cur->code }})
                                        {{ $cur->is_default ? ' ★ [الأساسية]' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text text-muted small">
                                العملة التي تم الاتفاق عليها مع الموظف.
                            </div>
                        </div>

                        {{-- معامل التحويل / سعر الصرف --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold d-flex justify-content-between align-items-center">
                                <span>{{ __('معامل التحويل (سعر الصرف)') }}</span>
                                <button type="button" id="btn_fetch_live_rate" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 0.75rem;" title="تحديث سعر اليوم من الـ API تلقائياً">
                                    <i class="fas fa-sync-alt me-1" id="icon_fetch_live_rate"></i>{{ __('سعر اليوم تلقائي') }}
                                </button>
                            </label>
                            <div class="input-group">
                                <input type="number"
                                       name="exchange_rate"
                                       id="emp_exchange_rate"
                                       class="form-control font-monospace"
                                       value="{{ $initialExchangeRate }}"
                                       step="0.00000001"
                                       min="0.00000001"
                                       placeholder="1.00000000">
                                <span class="input-group-text small bg-light" id="emp_rate_label">
                                    {{ $defaultCurrency->symbol ?? $defaultCurrency->code }}
                                </span>
                            </div>
                            <div class="form-text text-muted small" id="emp_rate_hint">
                                قابل للتعديل يدوياً لحركات التعيين القديمة.
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        {{-- مبلغ الراتب بعملة الموظف --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                {{ __('الراتب (بعملة المعاملة)') }}
                            </label>
                            <div class="input-group">
                                <span class="input-group-text" id="emp_currency_symbol">
                                    {{ $selectedCurrency->symbol ?? ($defaultCurrency->symbol ?? '') }}
                                </span>
                                <input type="number"
                                       name="currency_salary"
                                       id="emp_currency_salary"
                                       class="form-control fw-bold"
                                       value="{{ $currencySalary }}"
                                       step="0.01"
                                       min="0"
                                       placeholder="0.00">
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>

                        {{-- نوع استحقاق الراتب --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold">{{ __('hr::hr.salary_type') }}</label>
                            <select name="salary_type" class="form-select">
                                <option value="">{{ __('اختر نوع الاستحقاق') }}</option>
                                @foreach ([
                                    'ساعات عمل فقط',
                                    'ساعات عمل و إضافي يومى',
                                    'ساعات عمل و إضافي للمده',
                                    'حضور فقط',
                                    'إنتاج فقط',
                                    'ثابت + ساعات عمل مرن',
                                ] as $type)
                                    <option value="{{ $type }}" @selected(old('salary_type', $p['salary_type'] ?? '') === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    {{-- حقل مخفي للراتب بالعملة الأساسية (المعتمد في القيود المحاسبية) --}}
                    <input type="hidden" name="salary" id="emp_base_salary_hidden" value="{{ $baseSalary }}">

                    {{-- بطاقة توضيحية للأثر على القيود المحاسبية --}}
                    <div class="alert alert-info border-info-subtle mb-0 py-2 px-3 rounded-3 shadow-none">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <i class="fas fa-book-open me-2 text-primary"></i>
                                <span class="fw-bold text-dark">الراتب المعادل بالعملة الأساسية (للقيود والدفاتر):</span>
                                <div class="text-muted small mt-1">
                                    <i class="fas fa-shield-alt text-success me-1"></i>
                                    يتم تسجيل القيود اليومية وحسابات التكلفة بالعملة الأساسية للنظام تلقائياً.
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary fs-6 px-3 py-2 font-monospace">
                                    <span id="preview_base_salary">{{ number_format((float)($baseSalary ?: 0), 2) }}</span>
                                    <span id="preview_base_currency_symbol">{{ $defaultCurrency->symbol ?? $defaultCurrency->code }}</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- كرت الحسابات الإضافية --}}
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-gradient-info text-white py-2">
                    <h6 class="card-title mb-0 font-hold fw-bold">
                        <i class="fas fa-calculator me-2"></i>{{ __('حسابات إضافية') }}
                    </h6>
                </div>
                <div class="card-body py-3">
                    @foreach ([
                        'additional_hour_calculation' => 'الساعة الإضافي تحسب ك',
                        'additional_day_calculation' => 'اليوم الإضافي يحسب ك',
                        'late_hour_calculation' => 'الساعة المتأخرة تحسب ك',
                        'late_day_calculation' => 'اليوم الغياب يحسب ك',
                        'flexible_hourly_wage' => __('hr::hr.flexible_hourly_wage'),
                    ] as $field => $label)
                        <div class="mb-3">
                            <label class="form-label fw-bold">{{ $label }}</label>
                            <input type="text" name="{{ $field }}" class="form-control"
                                   value="{{ old($field, $p[$field] ?? '') }}" placeholder="0">
                            <div class="invalid-feedback"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- سكريبت التفاعل والتحويل التلقائي --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const currencySelect = document.getElementById('emp_currency_id');
    const exchangeRateInput = document.getElementById('emp_exchange_rate');
    const currencySalaryInput = document.getElementById('emp_currency_salary');
    const baseSalaryHidden = document.getElementById('emp_base_salary_hidden');
    const previewBaseSalary = document.getElementById('preview_base_salary');
    const currencySymbolSpan = document.getElementById('emp_currency_symbol');
    const rateHint = document.getElementById('emp_rate_hint');
    const btnFetchLive = document.getElementById('btn_fetch_live_rate');
    const iconFetchLive = document.getElementById('icon_fetch_live_rate');

    if (!currencySelect || !exchangeRateInput || !currencySalaryInput) return;

    // دالة حساب وتحديث الراتب بالعملة الأساسية
    function updateBaseSalaryCalculation() {
        const salaryVal = parseFloat(currencySalaryInput.value) || 0;
        const rateVal = parseFloat(exchangeRateInput.value) || 1;
        const baseTotal = (salaryVal * rateVal);

        baseSalaryHidden.value = baseTotal.toFixed(2);
        if (previewBaseSalary) {
            previewBaseSalary.textContent = baseTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }

    // دالة التعامل مع تغيير العملة
    function handleCurrencyChange(isUserAction = false) {
        const selectedOption = currencySelect.options[currencySelect.selectedIndex];
        if (!selectedOption) return;

        const isDefault = selectedOption.getAttribute('data-is-default') === '1';
        const symbol = selectedOption.getAttribute('data-symbol') || selectedOption.getAttribute('data-code') || '';
        const defaultRate = parseFloat(selectedOption.getAttribute('data-rate')) || 1;

        if (currencySymbolSpan) {
            currencySymbolSpan.textContent = symbol;
        }

        if (isDefault) {
            exchangeRateInput.value = '1.00000000';
            exchangeRateInput.readOnly = true;
            exchangeRateInput.classList.add('bg-light');
            if (btnFetchLive) btnFetchLive.disabled = true;
            if (rateHint) rateHint.textContent = 'العملة الأساسية للنظام (معامل التحويل ثابت = 1).';
        } else {
            exchangeRateInput.readOnly = false;
            exchangeRateInput.classList.remove('bg-light');
            if (btnFetchLive) btnFetchLive.disabled = false;
            if (isUserAction) {
                exchangeRateInput.value = defaultRate.toFixed(8);
            }
            if (rateHint) {
                rateHint.textContent = `1 ${selectedOption.getAttribute('data-code')} = ${exchangeRateInput.value} بالعملة الأساسية (يمكنك تعديله يدوياً).`;
            }
        }

        updateBaseSalaryCalculation();
    }

    currencySelect.addEventListener('change', function () {
        handleCurrencyChange(true);
    });

    exchangeRateInput.addEventListener('input', function () {
        const selectedOption = currencySelect.options[currencySelect.selectedIndex];
        if (selectedOption && rateHint && selectedOption.getAttribute('data-is-default') !== '1') {
            rateHint.textContent = `1 ${selectedOption.getAttribute('data-code')} = ${this.value || 0} بالعملة الأساسية (تم التعديل يدوياً).`;
        }
        updateBaseSalaryCalculation();
    });

    currencySalaryInput.addEventListener('input', updateBaseSalaryCalculation);

    // زر جلب سعر اليوم من الـ API تلقائياً
    if (btnFetchLive) {
        btnFetchLive.addEventListener('click', function () {
            const selectedOption = currencySelect.options[currencySelect.selectedIndex];
            if (!selectedOption || selectedOption.getAttribute('data-is-default') === '1') return;

            const liveUrl = selectedOption.getAttribute('data-live-url');
            if (!liveUrl) return;

            if (iconFetchLive) iconFetchLive.classList.add('fa-spin');
            btnFetchLive.disabled = true;

            fetch(liveUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.rate_raw) {
                    exchangeRateInput.value = parseFloat(data.rate_raw).toFixed(8);
                    selectedOption.setAttribute('data-rate', data.rate_raw);
                    updateBaseSalaryCalculation();
                    if (rateHint) {
                        rateHint.textContent = `تم جلب سعر اليوم بنجاح: 1 ${selectedOption.getAttribute('data-code')} = ${exchangeRateInput.value} (يمكن تعديله).`;
                    }
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'تم تحديث سعر الصرف لليوم بنجاح',
                            showConfirmButton: false,
                            timer: 2500
                        });
                    }
                } else {
                    throw new Error(data.message || 'تعذر جلب السعر');
                }
            })
            .catch(err => {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'warning',
                        title: err.message || 'لم نتمكن من جلب السعر اللحظي، يمكنك إدخاله يدوياً',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            })
            .finally(() => {
                if (iconFetchLive) iconFetchLive.classList.remove('fa-spin');
                btnFetchLive.disabled = false;
            });
        });
    }

    // تشغيل مبدئي عند فتح الشاشة
    handleCurrencyChange(false);
});
</script>
