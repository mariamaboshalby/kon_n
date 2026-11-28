@php $p = $p ?? []; @endphp
<div>
    <div class="row mt-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient-primary text-white py-2">
                    <h6 class="card-title mb-0 font-hold fw-bold">
                        <i class="fas fa-money-bill-wave me-2"></i>{{ __('hr::hr.accounting') }}
                    </h6>
                </div>
                <div class="card-body py-3">
                    <label class="form-label fw-bold">{{ __('يتبع للحساب') }}</label>
                    <select name="salary_basic_account_id" class="form-select">
                        <option value="">{{ __('اختر الحساب الرئيسي للمرتب') }}</option>
                        @foreach ($salary_basic_accounts as $account)
                            <option value="{{ $account->id }}"
                                @selected((string) old('salary_basic_account_id', $p['salary_basic_account_id'] ?? '') === (string) $account->id)>
                                {{ $account->code }} - {{ $account->aname }}
                            </option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient-primary text-white py-2">
                    <h6 class="card-title mb-0 font-hold fw-bold">
                        <i class="fas fa-money-bill-wave me-2"></i>{{ __('الأرصده') }}
                    </h6>
                </div>
                <div class="card-body py-3">
                    <label class="form-label fw-bold">{{ __('الرصيد الأفتتاحى') }}</label>
                    <div class="input-group">
                        <span class="input-group-text">{{ currency_symbol() }}</span>
                        <input type="text" name="opening_balance" class="form-control"
                               value="{{ old('opening_balance', $p['opening_balance'] ?? '') }}" placeholder="0.00">
                    </div>
                    <div class="invalid-feedback"></div>
                </div>
            </div>
        </div>
    </div>
</div>
