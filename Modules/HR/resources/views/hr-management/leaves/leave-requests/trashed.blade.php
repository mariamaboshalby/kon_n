@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.trashed_leave_requests'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.leave_requests'), 'url' => route('hr.leaves.requests.index')],
            ['label' => __('hr::hr.trashed_leave_requests')],
        ],
    ])

    <div class="hr-leave-requests-trashed" style="font-family: var(--masar-font-family); direction: rtl;">
        @if (session('message'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('message') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        <div id="hrLeaveRequestAlert" class="alert d-none" role="alert"></div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h3 class="card-title font-hold fw-bold mb-0">{{ __('hr::hr.trashed_leave_requests') }}</h3>
                <a href="{{ route('hr.leaves.requests.index') }}" class="btn btn-secondary font-hold fw-bold">
                    <i class="fas fa-arrow-right me-2"></i>{{ __('hr::hr.back_to_leave_requests') }}
                </a>
            </div>
            <div class="card-body">
                <div class="row mb-3 g-2">
                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold">{{ __('hr::hr.search') }}</label>
                        <input type="text" id="hrLeaveRequestSearch" class="form-control font-hold"
                               placeholder="{{ __('hr::hr.search_employee_name') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold">{{ __('hr::hr.employee') }}</label>
                        <select id="hrLeaveRequestEmployeeFilter" class="form-select font-hold fw-bold js-searchable-filter">
                            <option value="">{{ __('hr::hr.all_employees') }}</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold">{{ __('hr::hr.leave_type') }}</label>
                        <select id="hrLeaveRequestTypeFilter" class="form-select font-hold fw-bold">
                            <option value="">{{ __('hr::hr.all_leave_types') }}</option>
                            @foreach ($leaveTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold">{{ __('hr::hr.leave_status') }}</label>
                        <select id="hrLeaveRequestStatusFilter" class="form-select font-hold fw-bold">
                            <option value="">{{ __('hr::hr.all_statuses') }}</option>
                            <option value="draft">{{ __('hr::hr.draft') }}</option>
                            <option value="submitted">{{ __('hr::hr.submitted') }}</option>
                            <option value="approved">{{ __('hr::hr.approved') }}</option>
                            <option value="rejected">{{ __('hr::hr.rejected') }}</option>
                            <option value="cancelled">{{ __('hr::hr.cancelled') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold">{{ __('hr::hr.date_from') }}</label>
                        <input type="date" id="hrLeaveRequestDateFrom" class="form-control font-hold fw-bold">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold">{{ __('hr::hr.date_to') }}</label>
                        <input type="date" id="hrLeaveRequestDateTo" class="form-control font-hold fw-bold">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead class="table-light text-center">
                            <tr>
                                <th class="font-hold fw-bold">{{ __('hr::hr.employee') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.leave_type') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.start_date') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.end_date') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.duration') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.leave_status') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.deleted_at') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody id="hrLeaveRequestTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window._hrLeaveRequestsConfig = {
        page: 'trashed',
        routes: {
            data: @json(route('hr.leaves.requests.trashed.data')),
            restore: @json(route('hr.leaves.requests.restore', ['leaveRequest' => '__ID__'])),
            forceDestroy: @json(route('hr.leaves.requests.force-destroy', ['leaveRequest' => '__ID__']))
        },
        csrfToken: @json(csrf_token()),
        trans: {
            empty: @json(__('hr::hr.no_trashed_leave_requests')),
            loadError: @json(__('hr::hr.error_loading')),
            confirmRestore: @json(__('hr::hr.confirm_restore_leave_request')),
            confirmForceDelete: @json(__('hr::hr.confirm_force_delete_leave_request')),
            restore: @json(__('hr::hr.restore')),
            forceDelete: @json(__('hr::hr.force_delete'))
        }
    };
</script>
<script src="{{ asset('js/hr-leave-requests.js') }}"></script>
@endpush
