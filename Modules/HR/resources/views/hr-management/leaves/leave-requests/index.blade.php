@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.leave_requests'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.leave_requests')],
        ],
    ])

    <div class="hr-leave-requests" style="font-family: var(--masar-font-family); direction: rtl;">
        @if (session('message'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('message') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        <div id="hrLeaveRequestAlert" class="alert d-none" role="alert"></div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h3 class="card-title font-hold fw-bold mb-0">{{ __('hr::hr.leave_requests') }}</h3>
                <div class="d-flex gap-2">
                    @can('create Leave Requests')
                        <a href="{{ route('hr.leaves.requests.create') }}" class="btn btn-main font-hold fw-bold">
                            <i class="fas fa-plus me-2"></i>{{ __('hr::hr.add_leave_request') }}
                        </a>
                    @endcan
                    <a href="{{ route('hr.leaves.requests.trashed') }}" class="btn btn-warning font-hold fw-bold">
                        <i class="fas fa-trash me-2"></i>{{ __('hr::hr.trashed_leave_requests') }}
                    </a>
                </div>
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
                                <th class="font-hold fw-bold">{{ __('hr::hr.approved_by') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.submitted_at') }}</th>
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
        page: 'index',
        routes: {
            data: @json(route('hr.leaves.requests.data')),
            approve: @json(route('hr.leaves.requests.approve', ['leaveRequest' => '__ID__'])),
            reject: @json(route('hr.leaves.requests.reject', ['leaveRequest' => '__ID__'])),
            cancel: @json(route('hr.leaves.requests.cancel', ['leaveRequest' => '__ID__'])),
            destroy: @json(route('hr.leaves.requests.destroy', ['leaveRequest' => '__ID__']))
        },
        csrfToken: @json(csrf_token()),
        trans: {
            empty: @json(__('hr::hr.no_leave_requests_found')),
            loadError: @json(__('hr::hr.error_loading')),
            confirmDelete: @json(__('hr::hr.confirm_delete_leave')),
            confirmApprove: @json(__('hr::hr.confirm_approve_leave_request')),
            confirmReject: @json(__('hr::hr.confirm_reject_leave_request')),
            confirmCancel: @json(__('hr::hr.confirm_cancel_leave_request')),
            view: @json(__('hr::hr.view_leave_request')),
            edit: @json(__('hr::hr.edit_leave_request')),
            approve: @json(__('hr::hr.approve_request')),
            reject: @json(__('hr::hr.reject_request')),
            cancel: @json(__('hr::hr.cancel_request')),
            delete: @json(__('hr::hr.delete'))
        }
    };
</script>
<script src="{{ asset('js/hr-leave-requests.js') }}"></script>
@endpush
