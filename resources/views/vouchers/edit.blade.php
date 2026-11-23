@extends('admin.dashboard')

{{-- Dynamic Sidebar --}}
@section('sidebar')
    @include('components.sidebar.vouchers')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('navigation.vouchers'),
        'breadcrumb_items' => [
            ['label' => __('navigation.home'), 'url' => route('admin.dashboard')],
            ['label' => __('navigation.vouchers'), 'url' => route('vouchers.index')],
            ['label' => __('general.edit')],
        ],
    ])

    @php
        $typeMap = [
            1 => 'receipt',
            101 => 'payment',
            3 => 'exp-payment',
        ];
        $type = $typeMap[$voucher->pro_type] ?? 'receipt';
    @endphp

    @if ($type === 'receipt' || $type === 'payment' || $type === 'exp-payment')
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid"></div>
            </section>

            <section class="content">
                <form id="myForm" action="{{ route('vouchers.update', $voucher->id) }}" method="post">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="pro_type" value="{{ $voucher->pro_type }}">
                    <input type="hidden" name="currency_id" id="currency_id" value="{{ $voucher->currency_id }}">
                    <input type="hidden" name="currency_rate" id="currency_rate"
                        value="{{ $voucher->currency_rate ?? 1 }}">

                    <div class="card bg-white col-md-11 container">
                        <div class="card-header">
                            <h1 class="h1">@switch($type)
                                    @case('receipt')
                                        {{ __('vouchers.voucher_receipt') }}
                                    @break

                                    @case('payment')
                                        {{ __('vouchers.voucher_payment') }}
                                    @break

                                    @case('exp-payment')
                                        {{ __('vouchers.voucher_exp_payment') }}
                                    @break

                                    @default
                                        {{ __('vouchers.voucher_payment') }}
                                @endswitch
                            </h1>
                        </div>

                        <div class="card-body">
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <template x-if="currencyMismatch && isMultiCurrencyEnabled">
                                <div class="alert alert-danger">
                                    <i class="las la-exclamation-triangle me-2"></i>
                                    {{ __('vouchers.currency_mismatch') }}
                                </div>
                            </template>

                            {{-- Row 1: رقم العملية | المبلغ | العملة | معامل التحويل | المبلغ المحول | البيان --}}
                            <div class="row">
                                <div class="col-lg-1">
                                    <div class="form-group">
                                        <label for="pro_id">{{ __('vouchers.operation_number') }}</label>
                                        <input type="text" name="pro_id" class="form-control"
                                            value="{{ old('pro_id', $voucher->pro_id) }}" readonly>
                                    </div>
                                </div>

                                <div class="col-lg-2">
                                    <div class="form-group">
                                        <label for="pro_value">{{ __('vouchers.amount') }}</label>
                                        @php
                                            $displayValue = old('pro_value');
                                            if ($displayValue === null) {
                                                $displayValue = $voucher->pro_value;
                                                if ($voucher->currency_id && $voucher->currency_rate && $voucher->currency_rate > 0) {
                                                    $displayValue = $voucher->pro_value / $voucher->currency_rate;
                                                }
                                            }
                                            $displayValue = round((float)$displayValue, 2);
                                        @endphp
                                        <input type="number" step="0.01" name="pro_value" id="pro_value"
                                            class="form-control frst" value="{{ $displayValue }}">
                                    </div>
                                </div>

                                @if(isMultiCurrencyEnabled())
                                    <div class="col-lg-2">
                                        <div class="form-group">
                                            <label for="currency_selector">{{ __('vouchers.currency') }}</label>
                                            <select id="currency_selector" class="form-control">
                                                @foreach($allCurrencies as $currency)
                                                    <option value="{{ $currency->id }}"
                                                            data-rate="{{ $currency->latestRate->rate ?? 1 }}"
                                                            data-name="{{ $currency->name }}"
                                                            {{ $voucher->currency_id == $currency->id ? 'selected' : '' }}>
                                                        {{ $currency->name }} ({{ number_format($currency->latestRate->rate ?? 1, 2) }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-2">
                                        <div class="form-group">
                                            <label for="currency_rate_display">{{ __('vouchers.exchange_rate') }}</label>
                                            <input type="number" step="0.000001" min="0.000001"
                                                id="currency_rate_display"
                                                class="form-control"
                                                value="{{ number_format($voucher->currency_rate ?? 1, 6) }}"
                                                placeholder="1.000000">
                                        </div>
                                    </div>

                                    <div class="col-lg-2">
                                        <div class="form-group">
                                            <label for="converted_amount">{{ __('vouchers.converted_amount') }}</label>
                                            <input type="number" step="0.01" id="converted_amount" name="converted_amount"
                                                class="form-control"
                                                value="{{ number_format($voucher->pro_value, 2) }}"
                                                placeholder="0.00">
                                        </div>
                                    </div>
                                @endif

                                <div class="col-lg-{{ isMultiCurrencyEnabled() ? '3' : '7' }}">
                                    <div class="form-group">
                                        <label for="details">{{ __('vouchers.description') }}</label>
                                        <input type="text" name="details" class="form-control"
                                            placeholder="{{ __('vouchers.enter_description') }}"
                                            value="{{ old('details', $voucher->details) }}">
                                    </div>
                                </div>
                            </div>

                            {{-- Row 2: الصندوق | الحساب المقابل --}}
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label
                                            for="cash_account">{{ $type === 'receipt' ? __('vouchers.cash_bank') : __('vouchers.cash_account') }}</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <select name="{{ $type === 'receipt' ? 'acc1' : 'acc2' }}"
                                                typess="{{ $type }}" id="cash_account"
                                                class="form-control js-tom-select flex-grow-1">
                                                @if ($type === 'receipt')
                                                    <optgroup label="{{ __('vouchers.cash_boxes') }}">
                                                        @foreach ($cashAccounts as $account)
                                                            <option value="{{ $account->id }}"
                                                                data-balance="{{ $account->balance }}"
                                                                data-currency-id="{{ $account->currency_id }}"
                                                                data-currency-name="{{ $account->currency?->name ?? '' }}"
                                                                {{ ($voucher->pro_type == 1 ? $voucher->acc1 : $voucher->acc2) == $account->id ? 'selected' : '' }}>
                                                                {{ $account->aname }}
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                    @if ($bankAccounts->isNotEmpty())
                                                        <optgroup label="{{ __('vouchers.banks') }}">
                                                            @foreach ($bankAccounts as $account)
                                                                <option value="{{ $account->id }}"
                                                                    data-balance="{{ $account->balance }}"
                                                                    data-currency-id="{{ $account->currency_id }}"
                                                                    data-currency-name="{{ $account->currency?->name ?? '' }}"
                                                                    {{ ($voucher->pro_type == 1 ? $voucher->acc1 : $voucher->acc2) == $account->id ? 'selected' : '' }}>
                                                                    {{ $account->aname }}
                                                                </option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endif
                                                @else
                                                    @foreach ($cashAccounts as $account)
                                                        <option value="{{ $account->id }}"
                                                            data-balance="{{ $account->balance }}"
                                                            data-currency-id="{{ $account->currency_id }}"
                                                            data-currency-name="{{ $account->currency?->name ?? '' }}"
                                                            {{ ($voucher->pro_type == 1 ? $voucher->acc1 : $voucher->acc2) == $account->id ? 'selected' : '' }}>
                                                            {{ $account->aname }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            @if(isMultiCurrencyEnabled())
                                                <span id="cash_account_currency_badge" class="badge bg-info" style="display: none;"></span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row mt-1">
                                        <div class="col">{{ __('vouchers.before') }} : <span class="text-primary" id="cash_before">00.00</span>
                                        </div>
                                        <div class="col">{{ __('vouchers.after') }} : <span class="text-primary" id="cash_after">00.00</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label
                                            for="other_account">{{ $type === 'receipt' ? __('vouchers.credit_account') : __('vouchers.debit_account') }}</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <select name="{{ $type === 'receipt' ? 'acc2' : 'acc1' }}" id="other_account"
                                                class="form-control js-tom-select flex-grow-1">
                                                <option value="">{{ __('vouchers.select_account') }}</option>
                                                @if ($type == 'exp-payment')
                                                    @foreach ($paymentExpensesAccounts as $account)
                                                        <option value="{{ $account->id }}"
                                                            data-balance="{{ $account->balance }}"
                                                            data-currency-id="{{ $account->currency_id }}"
                                                            data-currency-name="{{ $account->currency?->name ?? '' }}"
                                                            {{ ($voucher->pro_type == 1 ? $voucher->acc2 : $voucher->acc1) == $account->id ? 'selected' : '' }}>
                                                            {{ $account->aname }}
                                                        </option>
                                                    @endforeach
                                                @elseif ($type == 'payment')
                                                    @foreach ($otherAccounts as $account)
                                                        <option value="{{ $account->id }}"
                                                            data-balance="{{ $account->balance }}"
                                                            data-currency-id="{{ $account->currency_id }}"
                                                            data-currency-name="{{ $account->currency?->name ?? '' }}"
                                                            {{ ($voucher->pro_type == 1 ? $voucher->acc2 : $voucher->acc1) == $account->id ? 'selected' : '' }}>
                                                            {{ $account->aname }}
                                                        </option>
                                                    @endforeach
                                                @else
                                                    @foreach ($otherAccounts as $account)
                                                        <option value="{{ $account->id }}"
                                                            data-balance="{{ $account->balance }}"
                                                            data-currency-id="{{ $account->currency_id }}"
                                                            data-currency-name="{{ $account->currency?->name ?? '' }}"
                                                            {{ ($voucher->pro_type == 1 ? $voucher->acc2 : $voucher->acc1) == $account->id ? 'selected' : '' }}>
                                                            {{ $account->aname }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            @if(isMultiCurrencyEnabled())
                                                <span id="other_account_currency_badge" class="badge bg-info" style="display: none;"></span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row mt-1">
                                        <div class="col">{{ __('vouchers.before') }} : <span id="acc_before">00.00</span></div>
                                        <div class="col">{{ __('vouchers.after') }} : <span id="acc_after">00.00</span></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Row 3: الرقم التسلسلي | رقم الايصال | التاريخ --}}
                            <div class="row">
                                <div class="col-lg-2">
                                    <div class="form-group">
                                        <label for="pro_serial">{{ __('vouchers.serial_number') }}</label>
                                        <input type="text" name="pro_serial" class="form-control"
                                            value="{{ old('pro_serial', $voucher->pro_serial) }}">
                                    </div>
                                </div>

                                <div class="col-lg-2">
                                    <div class="form-group">
                                        <label for="pro_num">{{ __('vouchers.receipt_number') }}</label>
                                        <input type="text" name="pro_num" class="form-control"
                                            value="{{ old('pro_num', $voucher->pro_num) }}">
                                    </div>
                                </div>

                                <div class="col-lg-3">
                                    <div class="form-group">
                                        <label for="pro_date">{{ __('vouchers.date') }}</label>
                                        <input type="date" name="pro_date" class="form-control"
                                            value="{{ old('pro_date', $voucher->pro_date) }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="emp_id">{{ __('vouchers.employee') }}</label>
                                        <select name="emp_id" class="form-control">
                                            @foreach ($employeeAccounts as $emp)
                                                <option value="{{ $emp->id }}"
                                                    {{ old('emp_id', $voucher->emp_id) == $emp->id ? 'selected' : '' }}>
                                                    {{ $emp->aname }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="emp2_id">{{ __('vouchers.collection_representative') }}</label>
                                        <select name="emp2_id" class="form-control">
                                            @foreach ($employeeAccounts as $emp)
                                                <option value="{{ $emp->id }}"
                                                    {{ old('emp2_id', $voucher->emp2_id) == $emp->id ? 'selected' : '' }}>
                                                    {{ $emp->aname }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label for="cost_center">{{ __('vouchers.cost_center') }}</label>
                                        <select name="cost_center" class="form-control">
                                            @foreach ($costCenters as $cost)
                                                <option value="{{ $cost->id }}"
                                                    {{ old('cost_center', $voucher->cost_center) == $cost->id ? 'selected' : '' }}>
                                                    {{ $cost->cname }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label for="project_id">{{ __('vouchers.project') }}</label>
                                        <select name="project_id" class="form-control">
                                            <option value="">{{ __('vouchers.select_project') }}</option>
                                            @foreach ($projects as $project)
                                                <option value="{{ $project->id }}"
                                                    {{ old('project_id', $voucher->project_id) == $project->id ? 'selected' : '' }}>
                                                    {{ $project->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-8">
                                    <div class="form-group">
                                        <label for="info">{{ __('vouchers.notes') }}</label>
                                        <input type="text" name="info" class="form-control"
                                            placeholder="{{ __('vouchers.enter_notes') }}" value="{{ old('info', $voucher->info) }}">
                                    </div>
                                </div>

                                <x-branches::branch-select :branches="$branches" :selected="$voucher->branch_id" />
                            </div>

                            {{-- Denominations Section --}}
                            @php
                                $existingDenominations = $voucher->denominations->keyBy('denomination');
                                $hasDenominations = $existingDenominations->isNotEmpty();
                            @endphp
                            <div class="row mt-3" x-data="denominationsManager({{ $hasDenominations ? 'true' : 'false' }}, @json($existingDenominations->map(fn($d) => $d->count)->toArray()))">
                                <div class="col-12">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="show_denominations"
                                            x-model="showTable"
                                            @change="showTable ? $nextTick(() => syncTotal()) : clearAll()">
                                        <label class="form-check-label fw-semibold" for="show_denominations">
                                            <i class="las la-money-bill-wave me-1"></i>
                                            {{ __('vouchers.show_denominations') }}
                                        </label>
                                    </div>

                                    <div x-show="showTable" x-transition>
                                        <div class="card border">
                                            <div class="card-header py-2 d-flex align-items-center justify-content-between">
                                                <span class="fw-semibold">{{ __('vouchers.denominations_title') }}</span>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="clearAll()">
                                                    <i class="las la-redo-alt me-1"></i>{{ __('vouchers.denominations_reset') }}
                                                </button>
                                            </div>
                                            <div class="card-body p-0">
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-sm mb-0 text-center align-middle">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>{{ __('vouchers.denomination_value') }}</th>
                                                                <template x-for="d in denominations" :key="d.value">
                                                                    <th x-text="d.value"></th>
                                                                </template>
                                                                <th class="table-warning">{{ __('vouchers.denomination_total') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td class="fw-semibold">{{ __('vouchers.denomination_count') }}</td>
                                                                <template x-for="d in denominations" :key="d.value">
                                                                    <td>
                                                                        <input type="number" min="0"
                                                                            class="form-control form-control-sm text-center"
                                                                            style="width:70px; margin:auto;"
                                                                            x-model.number="d.count"
                                                                            @input="syncTotal()"
                                                                            :name="'denomination_' + d.value">
                                                                    </td>
                                                                </template>
                                                                <td class="fw-bold table-warning">
                                                                    <span x-text="grandTotal.toFixed(2)"></span>
                                                                </td>
                                                            </tr>
                                                            <tr class="table-light">
                                                                <td class="fw-semibold">{{ __('vouchers.denomination_subtotal') }}</td>
                                                                <template x-for="d in denominations" :key="d.value">
                                                                    <td class="text-muted" x-text="(d.value * d.count).toFixed(2)"></td>
                                                                </template>
                                                                <td></td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="alert alert-info py-2 mt-2 d-flex align-items-center gap-2" x-show="mismatch" x-transition>
                                            <i class="las la-exclamation-circle fs-5"></i>
                                            <span>
                                                {{ __('vouchers.denomination_mismatch_prefix') }}
                                                <strong x-text="grandTotal.toFixed(2)"></strong>
                                                {{ __('vouchers.denomination_mismatch_suffix') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-main btn-lg">{{ __('vouchers.update') }}</button>
                            </div>
                        </div>
                </form>
            </section>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // Cash account balance calculation
                const cashAccount = document.getElementById("cash_account");
                const cashBefore = document.getElementById("cash_before");
                const cashAfter = document.getElementById("cash_after");
                const proValue = document.getElementById("pro_value");

                // Other account balance calculation
                const otherAccount = document.getElementById("other_account");
                const accBefore = document.getElementById("acc_before");
                const accAfter = document.getElementById("acc_after");

                // Update initial balances
                function updateBalances() {
                    // Get cash balance
                    let cashBalance = 0;
                    if (cashAccount.tomselect) {
                        // Using Tom Select
                        const selectedValue = cashAccount.tomselect.getValue();
                        if (selectedValue) {
                            const selectedOption = cashAccount.querySelector(`option[value="${selectedValue}"]`);
                            if (selectedOption && selectedOption.dataset.balance) {
                                cashBalance = parseFloat(selectedOption.dataset.balance);
                            }
                        }
                    } else {
                        // Using native select
                        const selectedCashOption = cashAccount.options[cashAccount.selectedIndex];
                        if (selectedCashOption && selectedCashOption.dataset.balance) {
                            cashBalance = parseFloat(selectedCashOption.dataset.balance);
                        }
                    }

                    // Get other account balance
                    let otherBalance = 0;
                    if (otherAccount.tomselect) {
                        // Using Tom Select
                        const selectedValue = otherAccount.tomselect.getValue();
                        if (selectedValue) {
                            const selectedOption = otherAccount.querySelector(`option[value="${selectedValue}"]`);
                            if (selectedOption && selectedOption.dataset.balance) {
                                otherBalance = parseFloat(selectedOption.dataset.balance);
                            }
                        }
                    } else {
                        // Using native select
                        const selectedOtherOption = otherAccount.options[otherAccount.selectedIndex];
                        if (selectedOtherOption && selectedOtherOption.dataset.balance) {
                            otherBalance = parseFloat(selectedOtherOption.dataset.balance);
                        }
                    }

                    const value = parseFloat(proValue.value) || 0;

                    // For receipt: cash increases, other account decreases
                    // For payment: cash decreases, other account increases
                    if ("{{ $type }}" === "receipt") {
                        cashAfter.textContent = (cashBalance + value).toFixed(2);
                        accAfter.textContent = (otherBalance - value).toFixed(2);
                    } else {
                        cashAfter.textContent = (cashBalance - value).toFixed(2);
                        accAfter.textContent = (otherBalance + value).toFixed(2);
                    }

                    cashBefore.textContent = cashBalance.toFixed(2);
                    accBefore.textContent = otherBalance.toFixed(2);
                }

                // Initialize Tom Select for searchable selects
                function initTomSelect() {
                    if (window.TomSelect) {
                        // Initialize cash_account
                        const cashAccountSelect = document.getElementById('cash_account');
                        if (cashAccountSelect && !cashAccountSelect.tomselect) {
                            const cashTomSelect = new TomSelect(cashAccountSelect, {
                                create: false,
                                searchField: ['text'],
                                sortField: {
                                    field: 'text',
                                    direction: 'asc'
                                },
                                dropdownInput: true,
                                placeholder: '{{ __('vouchers.search') }}',
                                onItemAdd: function() {
                                    updateBalances();
                                    updateCurrencyBadges();
                                },
                                onItemRemove: function() {
                                    updateBalances();
                                    updateCurrencyBadges();
                                }
                            });

                            // Set z-index for dropdown
                            cashTomSelect.on('dropdown_open', function() {
                                const dropdown = cashAccountSelect.parentElement.querySelector('.ts-dropdown');
                                if (dropdown) {
                                    dropdown.style.zIndex = '99999';
                                }
                            });
                        }

                        // Initialize other_account
                        const otherAccountSelect = document.getElementById('other_account');
                        if (otherAccountSelect && !otherAccountSelect.tomselect) {
                            const otherTomSelect = new TomSelect(otherAccountSelect, {
                                create: false,
                                searchField: ['text'],
                                sortField: {
                                    field: 'text',
                                    direction: 'asc'
                                },
                                dropdownInput: true,
                                placeholder: '{{ __('vouchers.search_account') }}',
                                onItemAdd: function() {
                                    updateBalances();
                                    updateCurrencyBadges();
                                },
                                onItemRemove: function() {
                                    updateBalances();
                                    updateCurrencyBadges();
                                }
                            });

                            // Set z-index for dropdown
                            otherTomSelect.on('dropdown_open', function() {
                                const dropdown = otherAccountSelect.parentElement.querySelector('.ts-dropdown');
                                if (dropdown) {
                                    dropdown.style.zIndex = '99999';
                                }
                            });
                        }
                    } else {
                        // Retry if Tom Select not loaded yet
                        setTimeout(initTomSelect, 100);
                    }
                }

                // Initialize Tom Select
                initTomSelect();

                // Event listeners for native selects (if not using Tom Select)
                if (!cashAccount.tomselect) {
                    cashAccount.addEventListener("change", function() {
                        updateBalances();
                        updateCurrencyBadges();
                    });
                }
                if (!otherAccount.tomselect) {
                    otherAccount.addEventListener("change", function() {
                        updateBalances();
                        updateCurrencyBadges();
                    });
                }
                proValue.addEventListener("input", function() {
                    updateBalances();
                    calculateConvertedAmount();
                });

                // Currency conversion calculation
                const currencySelector = document.getElementById('currency_selector');
                if (currencySelector) {
                    currencySelector.addEventListener('change', function() {
                        // عند تغيير العملة: نحدّث معامل التحويل من data-rate ثم نحسب
                        const selectedOption = this.options[this.selectedIndex];
                        const rateFromCurrency = parseFloat(selectedOption?.dataset?.rate) || 1;
                        const rateDisplay = document.getElementById('currency_rate_display');
                        if (rateDisplay) {
                            rateDisplay.value = rateFromCurrency.toFixed(6);
                        }
                        document.getElementById('currency_id').value = this.value || '1';
                        document.getElementById('currency_rate').value = rateFromCurrency;
                        calculateConvertedAmount();
                        updateBalances();
                    });
                }

                // تغيير معامل التحويل يدوياً → يعيد حساب المبلغ المحوّل فقط
                const rateDisplayInput = document.getElementById('currency_rate_display');
                if (rateDisplayInput) {
                    rateDisplayInput.addEventListener('input', function() {
                        const rate = parseFloat(this.value) || 1;
                        document.getElementById('currency_rate').value = rate;
                        calculateConvertedAmount();
                        updateBalances();
                    });
                }

                // تغيير converted_amount يدوياً → يعيد حساب pro_value عكسياً
                const convertedAmountInput = document.getElementById('converted_amount');
                if (convertedAmountInput) {
                    convertedAmountInput.addEventListener('input', function() {
                        const rate = parseFloat(document.getElementById('currency_rate')?.value) || 1;
                        if (rate > 0) {
                            proValue.value = (parseFloat(this.value) / rate).toFixed(2);
                        }
                        updateBalances();
                    });
                }

                function calculateConvertedAmount() {
                    const multiCurrencyEnabled = {{ isMultiCurrencyEnabled() ? 'true' : 'false' }};
                    if (!multiCurrencyEnabled) { return; }

                    const amount = parseFloat(proValue.value) || 0;
                    const convertedAmountField = document.getElementById('converted_amount');
                    const rateDisplay = document.getElementById('currency_rate_display');
                    if (!convertedAmountField) { return; }

                    // الأولوية: معامل التحويل المُدخل يدوياً
                    const rate = parseFloat(rateDisplay?.value) || 1;
                    convertedAmountField.value = (amount * rate).toFixed(2);

                    // تحديث الـ hidden fields
                    document.getElementById('currency_rate').value = rate;
                    const currencySelector = document.getElementById('currency_selector');
                    if (currencySelector) {
                        document.getElementById('currency_id').value = currencySelector.value || '1';
                    }
                }

                // Function to get currency ID from account
                function getAccountCurrencyId(accountElement) {
                    if (!accountElement) {
                        return null;
                    }

                    let selectedOption = null;

                    if (accountElement.tomselect) {
                        // Using Tom Select
                        const selectedValue = accountElement.tomselect.getValue();
                        if (selectedValue) {
                            selectedOption = accountElement.querySelector(`option[value="${selectedValue}"]`);
                        }
                    } else {
                        // Using native select
                        const selectedIndex = accountElement.selectedIndex;
                        if (selectedIndex >= 0) {
                            selectedOption = accountElement.options[selectedIndex];
                        }
                    }

                    if (selectedOption) {
                        // Try dataset first, then getAttribute as fallback
                        const currencyId = selectedOption.dataset.currencyId || selectedOption.getAttribute('data-currency-id');
                        return currencyId ? String(currencyId) : null;
                    }

                    return null;
                }

                // Function to get currency symbol from account
                function getAccountCurrencySymbol(accountElement) {
                    if (!accountElement) {
                        return '';
                    }

                    let selectedOption = null;

                    if (accountElement.tomselect) {
                        const selectedValue = accountElement.tomselect.getValue();
                        if (selectedValue) {
                            selectedOption = accountElement.querySelector(`option[value="${selectedValue}"]`);
                        }
                    } else {
                        const selectedIndex = accountElement.selectedIndex;
                        if (selectedIndex >= 0) {
                            selectedOption = accountElement.options[selectedIndex];
                        }
                    }

                    if (selectedOption) {
                        return selectedOption.dataset.currencyName || selectedOption.getAttribute('data-currency-name') || '';
                    }

                    return '';
                }

                // Function to update currency badges
                function updateCurrencyBadges() {
                    const multiCurrencyEnabled = {{ isMultiCurrencyEnabled() ? 'true' : 'false' }};
                    
                    if (!multiCurrencyEnabled) {
                        return;
                    }

                    const cashAccountEl = document.getElementById('cash_account');
                    const otherAccountEl = document.getElementById('other_account');
                    const cashBadge = document.getElementById('cash_account_currency_badge');
                    const otherBadge = document.getElementById('other_account_currency_badge');

                    if (cashAccountEl && cashBadge) {
                        const symbol = getAccountCurrencySymbol(cashAccountEl);
                        if (symbol) {
                            cashBadge.textContent = symbol;
                            cashBadge.style.display = 'inline-block';
                        } else {
                            cashBadge.style.display = 'none';
                        }
                    }

                    if (otherAccountEl && otherBadge) {
                        const symbol = getAccountCurrencySymbol(otherAccountEl);
                        if (symbol) {
                            otherBadge.textContent = symbol;
                            otherBadge.style.display = 'inline-block';
                        } else {
                            otherBadge.style.display = 'none';
                        }
                    }
                }

                // Function to check currency match and update hidden fields
                function checkAndUpdateCurrency() {
                    // التحقق من تفعيل تعدد العملات أولاً
                    const multiCurrencyEnabled = {{ isMultiCurrencyEnabled() ? 'true' : 'false' }};
                    
                    if (!multiCurrencyEnabled) {
                        // إذا كان تعدد العملات غير مفعل، استخدم القيم الافتراضية
                        document.getElementById('currency_id').value = '1';
                        document.getElementById('currency_rate').value = '1';
                        return true;
                    }

                    // الحصول على عناصر الحسابين
                    const cashAccountEl = document.getElementById('cash_account');
                    const otherAccountEl = document.getElementById('other_account');

                    if (!cashAccountEl || !otherAccountEl) {
                        return true; // Allow submission if elements not found
                    }

                    // الحصول على عملة الحسابين
                    const acc1CurrencyId = getAccountCurrencyId(cashAccountEl);
                    const acc2CurrencyId = getAccountCurrencyId(otherAccountEl);

                    // التحقق من أن الحسابين محددين
                    if (!acc1CurrencyId || !acc2CurrencyId) {
                        // إذا لم يتم اختيار الحسابين، استخدم القيم الافتراضية
                        document.getElementById('currency_id').value = '1';
                        document.getElementById('currency_rate').value = '1';
                        return true;
                    }

                    // التحقق من تطابق العملات
                    if (String(acc1CurrencyId) !== String(acc2CurrencyId)) {
                        alert('{{ __('vouchers.currency_mismatch') }}');
                        return false;
                    }

                    // إذا كانت العملات متطابقة، تعيين currency_id و currency_rate
                    const currencyRates = @json($allCurrencies->mapWithKeys(fn($c) => [$c->id => $c->latestRate->rate ?? 1]));
                    const currencyRate = currencyRates[acc1CurrencyId] || 1;

                    document.getElementById('currency_id').value = acc1CurrencyId;
                    document.getElementById('currency_rate').value = currencyRate;

                    return true;
                }

                // إضافة event listener على submit
                const form = document.getElementById('myForm');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        if (!checkAndUpdateCurrency()) {
                            e.preventDefault();
                            e.stopPropagation();
                            return false;
                        }
                    });
                }

                // Initial update
                updateBalances();
                updateCurrencyBadges();
                calculateConvertedAmount();
            });

            function denominationsManager(initialShow, savedCounts) {
                return {
                    showTable: initialShow,
                    denominations: [5, 10, 20, 50, 100, 200].map(v => ({
                        value: v,
                        count: savedCounts[v] ?? 0,
                    })),
                    grandTotal: 0,
                    proValue: 0,
                    get mismatch() {
                        if (!this.showTable || this.proValue <= 0 || this.grandTotal <= 0) return false;
                        return Math.round(this.grandTotal * 100) !== Math.round(this.proValue * 100);
                    },
                    init() {
                        const proValueEl = document.getElementById('pro_value');
                        if (proValueEl) {
                            this.proValue = parseFloat(proValueEl.value) || 0;
                            proValueEl.addEventListener('input', () => {
                                this.proValue = parseFloat(proValueEl.value) || 0;
                            });
                            this.$watch('mismatch', (val) => {
                                proValueEl.classList.toggle('is-invalid', val);
                            });
                        }
                        this.syncTotal();
                    },
                    syncTotal() {
                        this.grandTotal = this.denominations.reduce(
                            (sum, d) => sum + (d.value * (d.count || 0)), 0
                        );
                    },
                    clearAll() {
                        this.denominations.forEach(d => d.count = 0);
                        this.grandTotal = 0;
                    },
                };
            }
        </script>
    @endif
@endsection
