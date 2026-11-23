@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr.daily_employee_report'),
        'breadcrumb_items' => [
            ['label' => __('general.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr.human_resources')],
            ['label' => __('hr.attendance')],
            ['label' => __('hr.daily_employee_report')],
        ],
    ])

    <div class="container-fluid" x-data="{ activeTab: 'present' }">

        {{-- Filters --}}
        <div class="card mb-4 shadow-sm border-top border-4 border-primary">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                    <i class="las la-filter text-primary fs-4"></i>
                    {{ __('hr.report_filters') }}
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('hr.attendance.reports.daily') }}" class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label fw-bold">{{ __('hr.date_from') }}</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">{{ __('hr.date_to') }}</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">{{ __('hr.department') }}</label>
                        <select name="department_id" id="filter-department" class="form-select"
                                onchange="this.form.submit()">
                            <option value="">{{ __('hr.all_departments') }}</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" @selected($departmentId == $dept->id)>
                                    {{ $dept->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">{{ __('hr.employee') }}</label>
                        <select name="employee_id" class="form-select">
                            <option value="">{{ __('hr.all_employees') }}</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" @selected($employeeId == $emp->id)>
                                    {{ $emp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="las la-search me-1"></i>
                            {{ __('hr.generate_report') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Date Header --}}
        <div class="alert alert-light border d-flex align-items-center gap-2 mb-4">
            <i class="las la-calendar-day fs-4 text-primary"></i>
            @if($reportData['is_range'])
                <span class="fw-bold fs-5">
                    {{ $reportData['date_from_formatted'] }}
                    <span class="text-muted mx-2">—</span>
                    {{ $reportData['date_to_formatted'] }}
                </span>
            @else
                <span class="fw-bold fs-5">{{ $reportData['date_formatted'] }}</span>
            @endif
        </div>

        {{-- Summary Cards --}}
        <div class="row g-3 mb-4">
            @php
                $summary = $reportData['summary'];
                $cards = [
                    ['label' => __('hr.present'),           'count' => $summary['present_count'],                  'icon' => 'la-user-check',      'color' => 'success',  'tab' => 'present'],
                    ['label' => __('hr.absent'),            'count' => $summary['absent_count'],                   'icon' => 'la-user-times',      'color' => 'danger',   'tab' => 'absent'],
                    ['label' => __('hr.on_leave'),          'count' => $summary['on_leave_count'],                 'icon' => 'la-calendar-times',  'color' => 'info',     'tab' => 'on_leave'],
                    ['label' => __('hr.on_errand'),         'count' => $summary['on_errand_count'],                'icon' => 'la-route',           'color' => 'warning',  'tab' => 'on_errand'],
                    ['label' => __('hr.with_permission'),   'count' => $summary['with_permission_count'],          'icon' => 'la-user-clock',      'color' => 'secondary','tab' => 'with_permission'],
                    ['label' => __('hr.late'),              'count' => $summary['late_count'],                     'icon' => 'la-clock',           'color' => 'orange',   'tab' => 'late'],
                    ['label' => __('hr.pending_requests'),  'count' => $summary['pending_leave_requests_count'],   'icon' => 'la-hourglass-half',  'color' => 'purple',   'tab' => 'pending'],
                ];
            @endphp

            {{-- Total + Attendance % --}}
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm bg-primary text-white h-100">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small opacity-75 text-uppercase fw-bold">{{ __('hr.total_employees') }}</div>
                                <div class="fs-2 fw-bold">{{ $summary['total_employees'] }}</div>
                            </div>
                            <i class="las la-users fs-1 opacity-25"></i>
                        </div>
                        <div class="mt-2 pt-2 border-top border-white border-opacity-25">
                            <small class="opacity-75">{{ __('hr.attendance_percentage') }}</small>
                            <div class="fw-bold">{{ $summary['attendance_percentage'] }}%</div>
                        </div>
                    </div>
                </div>
            </div>

            @foreach($cards as $card)
                <div class="col-6 col-md-3">
                    <div class="card shadow-sm h-100 cursor-pointer border-bottom border-3 border-{{ $card['color'] }}"
                         style="cursor:pointer"
                         @click="activeTab = '{{ $card['tab'] }}'">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="small text-muted text-uppercase fw-bold">{{ $card['label'] }}</div>
                                    <div class="fs-2 fw-bold text-{{ $card['color'] }}">{{ $card['count'] }}</div>
                                </div>
                                <i class="las {{ $card['icon'] }} fs-1 text-{{ $card['color'] }} opacity-25"></i>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Tabs Navigation --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white p-0">
                <ul class="nav nav-tabs border-0 px-3 pt-2 flex-nowrap overflow-auto">
                    @php
                        $tabs = [
                            ['key' => 'present',         'label' => __('hr.present'),          'icon' => 'la-user-check',     'badge' => $summary['present_count'],                 'color' => 'success'],
                            ['key' => 'absent',          'label' => __('hr.absent'),           'icon' => 'la-user-times',     'badge' => $summary['absent_count'],                  'color' => 'danger'],
                            ['key' => 'on_leave',        'label' => __('hr.on_leave'),         'icon' => 'la-calendar-times', 'badge' => $summary['on_leave_count'],                'color' => 'info'],
                            ['key' => 'on_errand',       'label' => __('hr.on_errand'),        'icon' => 'la-route',          'badge' => $summary['on_errand_count'],               'color' => 'warning'],
                            ['key' => 'with_permission', 'label' => __('hr.with_permission'),  'icon' => 'la-user-clock',     'badge' => $summary['with_permission_count'],         'color' => 'secondary'],
                            ['key' => 'late',            'label' => __('hr.late'),             'icon' => 'la-clock',          'badge' => $summary['late_count'],                    'color' => 'danger'],
                            ['key' => 'pending',         'label' => __('hr.pending_requests'), 'icon' => 'la-hourglass-half', 'badge' => $summary['pending_leave_requests_count'],  'color' => 'warning'],
                        ];
                    @endphp
                    @foreach($tabs as $tab)
                        <li class="nav-item">
                            <button class="nav-link d-flex align-items-center gap-1 px-3 py-2"
                                    :class="activeTab === '{{ $tab['key'] }}' ? 'active fw-bold' : ''"
                                    @click="activeTab = '{{ $tab['key'] }}'">
                                <i class="las {{ $tab['icon'] }}"></i>
                                {{ $tab['label'] }}
                                <span class="badge bg-{{ $tab['color'] }} ms-1">{{ $tab['badge'] }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="card-body p-0">

                {{-- Present Tab --}}
                <div x-show="activeTab === 'present'" x-cloak>
                    @include('hr::hr-management.attendances.reports.partials.daily-report-table', [
                        'rows'    => $reportData['present'],
                        'columns' => $reportData['is_range']
                            ? ['date', 'name', 'department', 'shift', 'check_in_time', 'check_out_time']
                            : ['name', 'department', 'shift', 'check_in_time', 'check_out_time'],
                        'empty'   => __('hr.no_present_employees'),
                    ])
                </div>

                {{-- Absent Tab --}}
                <div x-show="activeTab === 'absent'" x-cloak>
                    @include('hr::hr-management.attendances.reports.partials.daily-report-table', [
                        'rows'    => $reportData['absent'],
                        'columns' => $reportData['is_range']
                            ? ['date', 'name', 'department', 'shift']
                            : ['name', 'department', 'shift'],
                        'empty'   => __('hr.no_absent_employees'),
                    ])
                </div>

                {{-- On Leave Tab --}}
                <div x-show="activeTab === 'on_leave'" x-cloak>
                    @include('hr::hr-management.attendances.reports.partials.daily-report-table', [
                        'rows'    => $reportData['on_leave'],
                        'columns' => $reportData['is_range']
                            ? ['date', 'name', 'department', 'leave_type', 'leave_start', 'leave_end', 'leave_duration', 'is_paid']
                            : ['name', 'department', 'leave_type', 'leave_start', 'leave_end', 'leave_duration', 'is_paid'],
                        'empty'   => __('hr.no_employees_on_leave'),
                    ])
                </div>

                {{-- On Errand Tab --}}
                <div x-show="activeTab === 'on_errand'" x-cloak>
                    @include('hr::hr-management.attendances.reports.partials.daily-report-table', [
                        'rows'    => $reportData['on_errand'],
                        'columns' => $reportData['is_range']
                            ? ['date', 'name', 'department', 'errand_title', 'errand_start', 'errand_end']
                            : ['name', 'department', 'errand_title', 'errand_start', 'errand_end'],
                        'empty'   => __('hr.no_employees_on_errand'),
                    ])
                </div>

                {{-- With Permission Tab --}}
                <div x-show="activeTab === 'with_permission'" x-cloak>
                    @include('hr::hr-management.attendances.reports.partials.daily-report-table', [
                        'rows'    => $reportData['with_permission'],
                        'columns' => $reportData['is_range']
                            ? ['date', 'name', 'department', 'permission_reason', 'permission_date']
                            : ['name', 'department', 'permission_reason', 'permission_date'],
                        'empty'   => __('hr.no_employees_with_permission'),
                    ])
                </div>

                {{-- Late Tab --}}
                <div x-show="activeTab === 'late'" x-cloak>
                    @include('hr::hr-management.attendances.reports.partials.daily-report-table', [
                        'rows'    => $reportData['late'],
                        'columns' => $reportData['is_range']
                            ? ['date', 'name', 'department', 'shift', 'check_in_time', 'late_minutes']
                            : ['name', 'department', 'shift', 'check_in_time', 'late_minutes'],
                        'empty'   => __('hr.no_late_employees'),
                    ])
                </div>

                {{-- Pending Leave Requests Tab --}}
                <div x-show="activeTab === 'pending'" x-cloak>
                    @if(count($reportData['pending_leave_requests']) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light text-muted">
                                    <tr>
                                        <th class="px-4 py-3">#</th>
                                        <th class="py-3">{{ __('hr.employee_name') }}</th>
                                        <th class="py-3">{{ __('hr.department') }}</th>
                                        <th class="py-3">{{ __('hr.leave_type') }}</th>
                                        <th class="py-3">{{ __('hr.leave_start_date') }}</th>
                                        <th class="py-3">{{ __('hr.leave_end_date') }}</th>
                                        <th class="py-3 text-center">{{ __('hr.leave_days_count') }}</th>
                                        <th class="py-3">{{ __('hr.leave_reason') }}</th>
                                        <th class="py-3 text-center">{{ __('hr.status') }}</th>
                                        <th class="py-3 text-center">{{ __('hr.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportData['pending_leave_requests'] as $i => $req)
                                        <tr>
                                            <td class="px-4 py-3 text-muted">{{ $i + 1 }}</td>
                                            <td class="py-3 fw-bold">{{ $req['employee_name'] }}</td>
                                            <td class="py-3 text-muted">{{ $req['department'] ?: '-' }}</td>
                                            <td class="py-3">{{ $req['leave_type'] }}</td>
                                            <td class="py-3">{{ $req['start_date'] }}</td>
                                            <td class="py-3">{{ $req['end_date'] }}</td>
                                            <td class="py-3 text-center">
                                                <span class="badge bg-info">{{ $req['duration_days'] }} {{ __('hr.days') }}</span>
                                            </td>
                                            <td class="py-3 text-muted small">{{ $req['reason'] ?: '-' }}</td>
                                            <td class="py-3 text-center">
                                                @if($req['status'] === 'submitted')
                                                    <span class="badge bg-warning text-dark">{{ __('hr.submitted') }}</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ __('hr.draft') }}</span>
                                                @endif
                                            </td>
                                            <td class="py-3 text-center">
                                                <a href="{{ route('hr.leaves.requests.show', $req['id']) }}"
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="las la-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="las la-check-circle fs-1 text-success opacity-50 d-block mb-2"></i>
                            <p class="text-muted">{{ __('hr.no_pending_leave_requests') }}</p>
                        </div>
                    @endif
                </div>

            </div>
        </div>

    </div>

    <style>
        [x-cloak] { display: none !important; }
        .nav-tabs .nav-link { border-radius: 0; border: none; border-bottom: 3px solid transparent; color: #6c757d; white-space: nowrap; }
        .nav-tabs .nav-link.active { border-bottom-color: #0d6efd; color: #0d6efd; background: transparent; }
        .nav-tabs .nav-link:hover:not(.active) { border-bottom-color: #dee2e6; color: #495057; }
    </style>
@endsection
