@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.leave_balances'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.leave_balances')],
        ],
    ])

    <div class="hr-leave-balances" style="font-family: var(--masar-font-family); direction: rtl;">
        @if (session('message'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('message') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        <div id="hrLeaveBalanceAlert" class="alert d-none" role="alert"></div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h3 class="card-title font-hold fw-bold mb-0">{{ __('hr::hr.leave_balances') }}</h3>
                @can('create Leave Balances')
                    <a href="{{ route('hr.leaves.balances.create') }}" class="btn btn-main font-hold fw-bold">
                        <i class="fas fa-plus me-2"></i>{{ __('hr::hr.add_leave_balance') }}
                    </a>
                @endcan
            </div>
            <div class="card-body">
                <div class="row mb-3 g-2">
                    <div class="col-md-3">
                        <label class="form-label font-hold fw-bold">{{ __('hr::hr.search') }}</label>
                        <input type="text" id="hrLeaveBalanceSearch" class="form-control font-hold fw-bold"
                               placeholder="{{ __('hr::hr.search_employee_name') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label font-hold fw-bold">{{ __('hr::hr.employee') }}</label>
                        <select id="hrLeaveBalanceEmployeeFilter" class="form-select font-hold fw-bold js-searchable-filter">
                            <option value="">{{ __('hr::hr.all_employees') }}</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label font-hold fw-bold">{{ __('hr::hr.leave_type') }}</label>
                        <select id="hrLeaveBalanceTypeFilter" class="form-select font-hold fw-bold">
                            <option value="">{{ __('hr::hr.all_leave_types') }}</option>
                            @foreach ($leaveTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label font-hold fw-bold">{{ __('hr::hr.year') }}</label>
                        <select id="hrLeaveBalanceYearFilter" class="form-select font-hold fw-bold">
                            @for ($year = now()->year + 1; $year >= now()->year - 2; $year--)
                                <option value="{{ $year }}" @selected($year === $selectedYear)>{{ $year }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead class="table-light text-center">
                            <tr>
                                <th class="font-hold fw-bold">{{ __('hr::hr.employee') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.leave_type') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.year') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.opening_balance_days') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.used_days') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.pending_days') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.max_monthly_days') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.remaining_days') }}</th>
                                @canany(['edit Leave Balances', 'delete Leave Balances'])
                                    <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                                @endcanany
                            </tr>
                        </thead>
                        <tbody id="hrLeaveBalanceTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window._hrLeaveBalancesConfig = {
        page: 'index',
        routes: {
            data: @json(route('hr.leaves.balances.data')),
            edit: @json(route('hr.leaves.balances.edit', ['balance' => '__ID__'])),
            destroy: @json(route('hr.leaves.balances.destroy', ['balance' => '__ID__']))
        },
        canEdit: @json(auth()->user()->can('edit Leave Balances')),
        canDelete: @json(auth()->user()->can('delete Leave Balances')),
        csrfToken: @json(csrf_token()),
        trans: {
            empty: @json(__('hr::hr.no_leave_balances_found')),
            confirmDelete: @json(__('hr::hr.confirm_delete_leave_balance')),
            loadError: @json(__('hr::hr.error_loading'))
        }
    };
</script>
<script src="{{ asset('js/hr-leave-balances.js') }}"></script>
@endpush
