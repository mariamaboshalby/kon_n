@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => $balance ? __('hr::hr.edit_leave_balance') : __('hr::hr.add_leave_balance'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.leave_balances'), 'url' => route('hr.leaves.balances.index')],
            ['label' => $balance ? __('hr::hr.edit_leave_balance') : __('hr::hr.add_leave_balance')],
        ],
    ])

    @php
        $opening = old('opening_balance_days', $balance?->opening_balance_days ?? 0);
        $used = old('used_days', $balance?->used_days ?? 0);
        $pending = old('pending_days', $balance?->pending_days ?? 0);
        $remaining = (float) $opening - (float) $used - (float) $pending;
    @endphp

    <div class="hr-leave-balances-form" style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrLeaveBalanceAlert" class="alert d-none" role="alert"></div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title font-hold fw-bold mb-0">
                    {{ $balance ? __('hr::hr.edit_leave_balance') : __('hr::hr.add_leave_balance') }}
                </h3>
                <a href="{{ route('hr.leaves.balances.index') }}" class="btn btn-secondary font-hold fw-bold">
                    <i class="fas fa-arrow-left"></i>
                    {{ __('hr::hr.back_to_list') }}
                </a>
            </div>
            <div class="card-body">
                <form id="hrLeaveBalanceForm">
                    <div id="hrLeaveBalanceGeneralError" class="alert alert-danger d-none font-hold fw-bold"></div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="employee_id" class="form-label font-hold fw-bold">{{ __('hr::hr.employee') }} <span class="text-danger">*</span></label>
                            <select name="employee_id" id="employee_id" class="form-select font-hold fw-bold js-searchable-filter" required>
                                <option value="">{{ __('hr::hr.select_employee') }}</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}" @selected((string) old('employee_id', $balance?->employee_id) === (string) $employee->id)>{{ $employee->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="leave_type_id" class="form-label font-hold fw-bold">{{ __('hr::hr.leave_type') }} <span class="text-danger">*</span></label>
                            <select name="leave_type_id" id="leave_type_id" class="form-select font-hold fw-bold" required>
                                <option value="">{{ __('hr::hr.select_leave_type') }}</option>
                                @foreach ($leaveTypes as $type)
                                    <option value="{{ $type->id }}" @selected((string) old('leave_type_id', $balance?->leave_type_id) === (string) $type->id)>{{ $type->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="year" class="form-label font-hold fw-bold">{{ __('hr::hr.year') }} <span class="text-danger">*</span></label>
                            <input type="text" name="year" id="year" inputmode="numeric"
                                   value="{{ old('year', $balance?->year ?? now()->year) }}"
                                   class="form-control font-hold fw-bold" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="opening_balance_days" class="form-label font-hold fw-bold">{{ __('hr::hr.opening_balance_days') }} <span class="text-danger">*</span></label>
                            <input type="text" name="opening_balance_days" id="opening_balance_days" inputmode="decimal"
                                   value="{{ $opening }}" class="form-control font-hold fw-bold hr-lb-calc" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="used_days" class="form-label font-hold fw-bold">{{ __('hr::hr.used_days') }} <span class="text-danger">*</span></label>
                            <input type="text" name="used_days" id="used_days" inputmode="decimal"
                                   value="{{ $used }}" class="form-control font-hold fw-bold hr-lb-calc" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="pending_days" class="form-label font-hold fw-bold">{{ __('hr::hr.pending_days') }} <span class="text-danger">*</span></label>
                            <input type="text" name="pending_days" id="pending_days" inputmode="decimal"
                                   value="{{ $pending }}" class="form-control font-hold fw-bold hr-lb-calc" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="max_monthly_days" class="form-label font-hold fw-bold">{{ __('hr::hr.max_monthly_days') }} <span class="text-danger">*</span></label>
                            <input type="text" name="max_monthly_days" id="max_monthly_days" inputmode="decimal"
                                   value="{{ old('max_monthly_days', $balance?->max_monthly_days ?? 0) }}"
                                   class="form-control font-hold fw-bold" required
                                   placeholder="{{ __('hr::hr.max_monthly_days_placeholder') }}">
                            <small class="form-text text-muted font-hold">{{ __('hr::hr.max_monthly_days_help') }}</small>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label font-hold fw-bold">{{ __('hr::hr.notes') }}</label>
                        <textarea name="notes" id="notes" rows="3" class="form-control font-hold"
                                  placeholder="{{ __('hr::hr.additional_notes_placeholder') }}">{{ old('notes', $balance?->notes) }}</textarea>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="card bg-light mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0 font-hold fw-bold">{{ __('hr::hr.balance_summary') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <h3 class="text-primary" id="hrLbRemaining">{{ number_format($remaining, 1) }}</h3>
                                    <p class="text-muted mb-0 font-hold">{{ __('hr::hr.remaining_days') }}</p>
                                </div>
                                <div class="col-md-4">
                                    <h3 class="text-success" id="hrLbOpening">{{ number_format((float) $opening, 1) }}</h3>
                                    <p class="text-muted mb-0 font-hold">{{ __('hr::hr.total_balance') }}</p>
                                </div>
                                <div class="col-md-4">
                                    <h3 class="text-danger" id="hrLbUsedTotal">{{ number_format((float) $used + (float) $pending, 1) }}</h3>
                                    <p class="text-muted mb-0 font-hold">{{ __('hr::hr.total_used') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <a href="{{ route('hr.leaves.balances.index') }}" class="btn btn-secondary font-hold fw-bold">{{ __('hr::hr.cancel') }}</a>
                        <button type="submit" class="btn btn-main font-hold fw-bold" id="hrLeaveBalanceSubmitBtn">
                            {{ $balance ? __('hr::hr.update') : __('hr::hr.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window._hrLeaveBalancesConfig = {
        page: 'form',
        routes: {
            store: @json(route('hr.leaves.balances.store')),
            update: @json($balance ? route('hr.leaves.balances.update', $balance) : '')
        },
        isEdit: @json((bool) $balance),
        csrfToken: @json(csrf_token()),
        trans: {
            saving: @json(__('hr::hr.saving')),
            saveError: @json(__('hr::hr.error_loading'))
        }
    };
</script>
<script src="{{ asset('js/hr-leave-balances.js') }}"></script>
@endpush
