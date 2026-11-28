@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.attendance_processing'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.attendance_processing')],
        ],
    ])

    <div style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrProcAlert" class="alert d-none" role="alert"></div>

        <div class="card mb-3">
            <div class="card-header font-hold fw-bold">{{ __('hr::hr.new_processing') }}</div>
            <div class="card-body">
                <form id="hrProcForm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('hr::hr.processing_type') }} *</label>
                            <select name="processing_type" id="hrProcType" class="form-select">
                                <option value="single">{{ __('hr::hr.single_employee') }}</option>
                                <option value="multiple">{{ __('hr::hr.multiple_employees') }}</option>
                                <option value="department">{{ __('hr::hr.department') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="hrProcEmployeeWrap">
                            <label class="form-label">{{ __('hr::hr.employee') }}</label>
                            <select name="selected_employee" id="hrProcEmployeeSelect" class="form-select js-searchable-filter"
                                data-placeholder="{{ __('hr::hr.search_employee_placeholder') }}">
                                <option value="">{{ __('hr::hr.select_employee') }}</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 d-none" id="hrProcEmployeesWrap">
                            <label class="form-label">{{ __('hr::hr.employees') }}</label>
                            <select name="selected_employees[]" id="hrProcEmployeesSelect" class="form-select js-searchable-filter" multiple
                                data-placeholder="{{ __('hr::hr.select_employees') }}">
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 d-none" id="hrProcDeptWrap">
                            <label class="form-label">{{ __('hr::hr.department') }}</label>
                            <select name="selected_department" class="form-select">
                                <option value="">{{ __('hr::hr.select_department') }}</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('hr::hr.date_from') }} *</label>
                            <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('hr::hr.date_to') }} *</label>
                            <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">{{ __('hr::hr.notes') }}</label>
                            <input type="text" name="notes" class="form-control">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            @can('create Attendance Processing')
                                <button type="submit" class="btn btn-main w-100" id="hrProcSubmitBtn">{{ __('hr::hr.process_attendance') }}</button>
                            @endcan
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header font-hold fw-bold">{{ __('hr::hr.previous_processings') }}</div>
            <div class="card-body table-responsive">
                <table class="table table-bordered text-center mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('hr::hr.employee') }}</th>
                            <th>{{ __('hr::hr.department') }}</th>
                            <th>{{ __('hr::hr.period') }}</th>
                            <th>{{ __('hr::hr.status') }}</th>
                            <th>{{ __('hr::hr.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody id="hrProcTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        .hr-proc-employee-report-link { text-decoration: none; font-weight: 600; }
        .hr-proc-employee-report-link:hover { text-decoration: underline; opacity: 0.85; }
    </style>
@endsection

@push('scripts')
<script>
    window._hrAttendanceProcessingConfig = {
        routes: {
            data: @json(route('hr.attendance.processing.data')),
            process: @json(route('hr.attendance.processing.process')),
            show: @json(route('hr.attendance.processing.show', ['processing' => '__ID__'])),
            approve: @json(route('hr.attendance.processing.approve', ['processing' => '__ID__'])),
            reject: @json(route('hr.attendance.processing.reject', ['processing' => '__ID__'])),
            destroy: @json(route('hr.attendance.processing.destroy', ['processing' => '__ID__'])),
            employeeComprehensive: @json(route('hr.attendance.reports.daily.employee-comprehensive', ['employee' => '__ID__']))
        },
        canEdit: @json(auth()->user()->can('edit Attendance Processing')),
        canDelete: @json(auth()->user()->can('delete Attendance Processing')),
        csrfToken: @json(csrf_token()),
        trans: {
            empty: @json(__('hr::hr.no_data')),
            confirmDelete: @json(__('hr::hr.confirm_delete')),
            confirmApprove: @json(__('hr::hr.confirm_approve')),
            confirmReject: @json(__('hr::hr.confirm_reject'))
        }
    };
</script>
<script src="{{ asset('js/hr-attendance-processing.js') }}"></script>
@endpush
