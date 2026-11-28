@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    <x-reports::report-page
        :title="__('hr.monthly_activity_report')"
        :breadcrumbs="[
            ['label' => __('general.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr.human_resources')],
            ['label' => __('hr.attendance')],
            ['label' => __('hr.monthly_activity_report')],
        ]"
        filter-collapse-id="hr-monthly-filters-collapse"
        print-only
        :with-card="false"
    >
    @php
        $summary = $reportData['summary'];
    @endphp

        <div class="collapse mb-3 report-filters-collapse" id="hr-monthly-filters-collapse">
            <div class="card mb-0">
                <div class="card-body">
                    <form method="GET" action="{{ route('hr.attendance.reports.monthly-activity') }}" class="report-filters">
                        <div class="row g-2 align-items-start">
                            <div class="col-12 col-md-6 col-lg-3 report-filters-col">
                                <div class="mb-2">
                                    <label class="form-label report-filter-label mb-1" for="hr-monthly-date-from">{{ __('hr.date_from') }}</label>
                                    <input type="date" name="date_from" id="hr-monthly-date-from" value="{{ $dateFrom }}" class="form-control report-filter-control">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label report-filter-label mb-1" for="hr-monthly-date-to">{{ __('hr.date_to') }}</label>
                                    <input type="date" name="date_to" id="hr-monthly-date-to" value="{{ $dateTo }}" class="form-control report-filter-control">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3 report-filters-col">
                                <div class="mb-0">
                                    <label class="form-label report-filter-label mb-1" for="hr-monthly-search">{{ __('hr.employee') }}</label>
                                    <input type="text" name="search_name" id="hr-monthly-search" value="{{ $searchName }}" class="form-control report-filter-control" placeholder="{{ __('hr.search_employee_name') }}">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3 report-filters-col">
                                <div class="d-flex flex-wrap gap-2 mt-1">
                                    <button type="submit" class="btn btn-sm btn-primaryyy">
                                        <i class="fas fa-filter me-1"></i>{{ __('hr.generate_report') }}
                                    </button>
                                    <a href="{{ route('hr.attendance.reports.monthly-activity') }}" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-redo me-1"></i>{{ __('reports::reports.reset') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ── Period Header ─────────────────────────────────────────────────── --}}
        <div class="alert alert-light border d-flex align-items-center gap-2 mb-4">
            <i class="las la-calendar-alt fs-4 text-primary"></i>
            <span class="fw-bold fs-5">{{ $reportData['period_label'] }}</span>
            <span class="badge bg-secondary ms-2">
                {{ $reportData['working_days'] }} {{ __('hr.working_days') }}
            </span>
        </div>

        {{-- ── KPI Summary Cards ─────────────────────────────────────────────── --}}
        <div class="row g-3 mb-4">

            {{-- Total Employees --}}
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card border-0 shadow-sm bg-primary text-white h-100">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="small opacity-75 text-uppercase fw-bold">{{ __('hr.total_employees') }}</div>
                                <div class="fs-2 fw-bold">{{ $summary['total_employees'] }}</div>
                            </div>
                            <i class="las la-users fs-1 opacity-25"></i>
                        </div>
                        <div class="mt-2 pt-2 border-top border-white border-opacity-25 small opacity-75">
                            {{ __('hr.working_days') }}: {{ $summary['working_days'] }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Avg Attendance % --}}
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card border-0 shadow-sm h-100 border-bottom border-3 border-success">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="small text-muted text-uppercase fw-bold">{{ __('hr.avg_attendance_pct') }}</div>
                                <div class="fs-3 fw-bold text-success">{{ $summary['total_present_days'] }}</div>
                                <small class="text-muted d-block">{{ $summary['avg_attendance_pct'] }}%</small>
                            </div>
                            <i class="las la-chart-pie fs-1 text-success opacity-25"></i>
                        </div>
                        <div class="mt-2">
                            <div class="progress" style="height:4px">
                                <div class="progress-bar bg-success"
                                     style="width:{{ $summary['avg_attendance_pct'] }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Perfect Attendance --}}
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card border-0 shadow-sm h-100 border-bottom border-3 border-info">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="small text-muted text-uppercase fw-bold">{{ __('hr.perfect_attendance') }}</div>
                                <div class="fs-2 fw-bold text-info">{{ $summary['perfect_attendance'] }}</div>
                            </div>
                            <i class="las la-award fs-1 text-info opacity-25"></i>
                        </div>
                        <div class="small text-muted mt-1">{{ __('hr.perfect_attendance_desc') }}</div>
                    </div>
                </div>
            </div>

            {{-- Total Absent Days --}}
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card border-0 shadow-sm h-100 border-bottom border-3 border-danger">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="small text-muted text-uppercase fw-bold">{{ __('hr.total_absent_days') }}</div>
                                <div class="fs-2 fw-bold text-danger">{{ $summary['total_absent_days'] }}</div>
                                <small class="text-muted d-block">{{ round(($summary['total_absent_days'] / ($summary['total_employees'] * $summary['working_days'])) * 100, 1) }}%</small>
                            </div>
                            <i class="las la-user-times fs-1 text-danger opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Leave Days --}}
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card border-0 shadow-sm h-100 border-bottom border-3 border-warning">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="small text-muted text-uppercase fw-bold">{{ __('hr.total_leave_days') }}</div>
                                <div class="fs-2 fw-bold text-warning">{{ $summary['total_leave_days'] }}</div>
                            </div>
                            <i class="las la-calendar-times fs-1 text-warning opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Work Hours --}}
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card border-0 shadow-sm h-100 border-bottom border-3 border-info">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="small text-muted text-uppercase fw-bold">ساعات العمل</div>
                                <div class="fs-2 fw-bold text-info">{{ number_format($summary['total_present_days'] * 8, 0) }}</div>
                            </div>
                            <i class="las la-hourglass-end fs-1 text-info opacity-25"></i>
                        </div>
                        <div class="small text-muted mt-2">
                            ~ {{ round(($summary['total_present_days'] * 8) / $summary['total_employees'], 1) }} سا/موظف
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Late Days --}}
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card border-0 shadow-sm h-100 border-bottom border-3 border-orange">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="small text-muted text-uppercase fw-bold">{{ __('hr.total_late_days') }}</div>
                                <div class="fs-2 fw-bold" style="color:#fd7e14">{{ $summary['total_late_days'] }}</div>
                                <small class="text-muted d-block">{{ round(($summary['total_late_days'] / ($summary['total_employees'] * $summary['working_days'])) * 100, 1) }}%</small>
                            </div>
                            <i class="las la-clock fs-1 opacity-25" style="color:#fd7e14"></i>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ── Department Breakdown ──────────────────────────────────────────── --}}
        @if(count($reportData['dept_totals']) > 0)
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
                <i class="las la-sitemap text-primary fs-4"></i>
                <h5 class="mb-0 fw-bold">{{ __('hr.dept_breakdown') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted">
                            <tr>
                                <th class="px-4 py-3">{{ __('hr.department') }}</th>
                                <th class="py-3 text-center">{{ __('hr.employee_count') }}</th>
                                <th class="py-3 text-center">{{ __('hr.avg_present') }}</th>
                                <th class="py-3 text-center">{{ __('hr.avg_absent') }}</th>
                                <th class="py-3 text-center">{{ __('hr.avg_leave') }}</th>
                                <th class="py-3 text-center">{{ __('hr.total_late') }}</th>
                                <th class="py-3 text-center">{{ __('hr.total_permissions_dept') }}</th>
                                <th class="py-3 text-center">{{ __('hr.avg_attendance_pct') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData['dept_totals'] as $dept)
                                <tr>
                                    <td class="px-4 py-3 fw-bold">{{ $dept['department'] }}</td>
                                    <td class="py-3 text-center">
                                        <span class="badge bg-primary text-white rounded-pill">{{ $dept['employee_count'] }}</span>
                                    </td>
                                    <td class="py-3 text-center text-success fw-bold">{{ $dept['avg_present'] }}</td>
                                    <td class="py-3 text-center text-danger fw-bold">{{ $dept['avg_absent'] }}</td>
                                    <td class="py-3 text-center text-warning fw-bold">{{ $dept['avg_leave'] }}</td>
                                    <td class="py-3 text-center">
                                        @if($dept['total_late'] > 0)
                                            <span class="badge bg-danger">{{ $dept['total_late'] }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 text-center">
                                        @if($dept['total_permissions'] > 0)
                                            <span class="badge bg-secondary">{{ $dept['total_permissions'] }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 text-center">
                                        @php
                                            $pct = $dept['avg_attendance_pct'];
                                            $color = $pct >= 90 ? 'success' : ($pct >= 75 ? 'warning' : 'danger');
                                        @endphp
                                        <div class="d-flex align-items-center gap-2 justify-content-center">
                                            <div class="progress flex-grow-1" style="height:6px;min-width:60px">
                                                <div class="progress-bar bg-{{ $color }}"
                                                     style="width:{{ $pct }}%"></div>
                                            </div>
                                            <span class="fw-bold text-{{ $color }} small">{{ $pct }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- ── Employee Breakdown ────────────────────────────────────────────── --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
                <i class="las la-users text-primary fs-4"></i>
                <h5 class="mb-0 fw-bold">{{ __('hr.employee_breakdown') }}</h5>
                <span class="badge bg-secondary ms-auto">{{ count($reportData['rows']) }}</span>
            </div>
            <div class="card-body p-0">
                @if(count($reportData['rows']) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted">
                                <tr>
                                    <th class="px-4 py-3">#</th>
                                    <th class="py-3">{{ __('hr.employee_name') }}</th>
                                    <th class="py-3">{{ __('hr.department') }}</th>
                                    <th class="py-3 text-center">{{ __('hr.present_days') }}</th>
                                    <th class="py-3 text-center">{{ __('hr.absent_days') }}</th>
                                    <th class="py-3 text-center">{{ __('hr.leave_days') }}</th>
                                    <th class="py-3 text-center">{{ __('hr.errand_days') }}</th>
                                    <th class="py-3 text-center">{{ __('hr.permission_count') }}</th>
                                    <th class="py-3 text-center">{{ __('hr.late_days') }}</th>
                                    <th class="py-3 text-center">{{ __('hr.attendance_pct') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reportData['rows'] as $i => $row)
                                    <tr>
                                        <td class="px-4 py-3 text-muted small">{{ $i + 1 }}</td>
                                        <td class="py-3">
                                            <a href="javascript:void(0)" 
                                               class="hr-employee-report-trigger text-primary text-decoration-none fw-bold d-inline-flex align-items-center gap-1"
                                               data-employee-id="{{ $row['employee_id'] }}"
                                               data-employee-name="{{ $row['employee_name'] }}"
                                               title="{{ __('hr.view_employee_report') }}"
                                               style="cursor: pointer;">
                                                <i class="las la-user-circle fs-5"></i>
                                                <span>{{ $row['employee_name'] }}</span>
                                            </a>
                                        </td>
                                        <td class="py-3 text-muted small">{{ $row['department'] }}</td>

                                        {{-- Present --}}
                                        <td class="py-3 text-center">
                                            <span class="badge bg-success bg-opacity-10 text-success fw-bold px-2">
                                                {{ $row['present_days'] }}
                                            </span>
                                        </td>

                                        {{-- Absent --}}
                                        <td class="py-3 text-center">
                                            @if($row['absent_days'] > 0)
                                                <span class="badge bg-danger bg-opacity-10 text-danger fw-bold px-2">
                                                    {{ $row['absent_days'] }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>

                                        {{-- Leave --}}
                                        <td class="py-3 text-center">
                                            @if($row['leave_days'] > 0)
                                                <span class="badge bg-info bg-opacity-10 text-info fw-bold px-2">
                                                    {{ $row['leave_days'] }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>

                                        {{-- Errand --}}
                                        <td class="py-3 text-center">
                                            @if($row['errand_days'] > 0)
                                                <span class="badge bg-warning bg-opacity-10 text-warning fw-bold px-2">
                                                    {{ $row['errand_days'] }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>

                                        {{-- Permissions --}}
                                        <td class="py-3 text-center">
                                            @if($row['permission_count'] > 0)
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary fw-bold px-2">
                                                    {{ $row['permission_count'] }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>

                                        {{-- Late --}}
                                        <td class="py-3 text-center">
                                            @if($row['late_days'] > 0)
                                                <span class="badge bg-danger bg-opacity-10 text-danger fw-bold px-2">
                                                    {{ $row['late_days'] }}
                                                </span>
                                            @else
                                                <span class="text-success small"><i class="las la-check"></i></span>
                                            @endif
                                        </td>

                                        {{-- Attendance % --}}
                                        <td class="py-3 text-center" style="min-width:110px">
                                            @php
                                                $pct   = $row['attendance_pct'];
                                                $color = $pct >= 90 ? 'success' : ($pct >= 75 ? 'warning' : 'danger');
                                            @endphp
                                            <div class="d-flex align-items-center gap-1 justify-content-center">
                                                <div class="progress flex-grow-1" style="height:5px;min-width:50px">
                                                    <div class="progress-bar bg-{{ $color }}"
                                                         style="width:{{ $pct }}%"></div>
                                                </div>
                                                <span class="fw-bold text-{{ $color }} small">{{ $pct }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="las la-inbox fs-1 text-muted opacity-50 d-block mb-2"></i>
                        <p class="text-muted">{{ __('hr.no_data_for_period') }}</p>
                    </div>
                @endif
            </div>
        </div>

    </x-reports::report-page>

    <style>
        .hr-employee-report-trigger:hover { text-decoration: underline !important; opacity: 0.85; }
        @media print {
            .card-header form,
            .btn { display: none !important; }
            .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
        }
    </style>
@endsection

@push('scripts')
<script>
(function($) {
    'use strict';

    var reportRouteTpl = @json(route('hr.attendance.reports.daily.employee-comprehensive', ['employee' => '__ID__']));

    // Click on employee name in monthly report table -> Navigate to dedicated comprehensive report page
    $(document).on('click', '.hr-employee-report-trigger', function(e) {
        e.preventDefault();
        var employeeId = $(this).data('employee-id');
        if (!employeeId) return;

        // Extract dates from monthly activity page filter inputs
        var dateFrom = $('input[name="date_from"]').val() || '';
        var dateTo = $('input[name="date_to"]').val() || dateFrom;

        var url = reportRouteTpl.replace('__ID__', employeeId) +
            '?date_from=' + encodeURIComponent(dateFrom) +
            '&date_to=' + encodeURIComponent(dateTo);

        window.location.href = url;
    });

})(jQuery);
</script>
@endpush

