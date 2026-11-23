@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr.monthly_activity_report'),
        'breadcrumb_items' => [
            ['label' => __('general.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr.human_resources')],
            ['label' => __('hr.attendance')],
            ['label' => __('hr.monthly_activity_report')],
        ],
    ])

    @php
        $summary = $reportData['summary'];
    @endphp

    <div class="container-fluid">

        {{-- ── Filters ──────────────────────────────────────────────────────── --}}
        <div class="card mb-4 shadow-sm border-top border-4 border-primary">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                    <i class="las la-filter text-primary fs-4"></i>
                    {{ __('hr.report_filters') }}
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('hr.attendance.reports.monthly-activity') }}"
                      class="row g-3 align-items-end">

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
                        <select name="department_id" class="form-select">
                            <option value="">{{ __('hr.all_departments') }}</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" @selected($departmentId == $dept->id)>
                                    {{ $dept->title }}
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

                    <div class="col-md-3 d-flex align-items-end justify-content-end">
                        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                            <i class="las la-print me-1"></i>
                            {{ __('general.print') }}
                        </button>
                    </div>
                </form>
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
                                <div class="fs-2 fw-bold text-success">{{ $summary['avg_attendance_pct'] }}%</div>
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

            {{-- Total Late Days --}}
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card border-0 shadow-sm h-100 border-bottom border-3 border-orange">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="small text-muted text-uppercase fw-bold">{{ __('hr.total_late_days') }}</div>
                                <div class="fs-2 fw-bold" style="color:#fd7e14">{{ $summary['total_late_days'] }}</div>
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
                                        <span class="badge bg-primary rounded-pill">{{ $dept['employee_count'] }}</span>
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
                                        <td class="py-3 fw-bold">{{ $row['employee_name'] }}</td>
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

    </div>

    <style>
        @media print {
            .card-header form,
            .btn { display: none !important; }
            .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
        }
    </style>
@endsection
