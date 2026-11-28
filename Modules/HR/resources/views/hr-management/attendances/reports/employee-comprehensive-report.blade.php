@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    <x-reports::report-page
        :title="__('hr.employee_comprehensive_report')"
        :subtitle="$employee->name"
        :breadcrumbs="[
            ['label' => __('general.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr.human_resources')],
            ['label' => __('hr.attendance')],
            ['label' => __('hr.daily_employee_report'), 'url' => route('hr.attendance.reports.daily', ['date_from' => $dateFrom, 'date_to' => $dateTo])],
            ['label' => $employee->name],
        ]"
        filter-collapse-id="hr-employee-filters-collapse"
        filter-toggle-id="hr-employee-filters-toggle"
        print-only
        :with-card="false"
    >
        <x-slot:afterFilter>
            <a href="{{ route('hr.attendance.reports.daily', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
               class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                <i class="las la-arrow-right"></i>
                <span>{{ __('general.back') }}</span>
            </a>
        </x-slot:afterFilter>

    <div class="container-fluid" x-data="{ activeTab: 'attendance' }">

        <div class="collapse mb-3 report-filters-collapse" id="hr-employee-filters-collapse">
            <div class="card mb-0">
                <div class="card-body">
                    <form method="GET" action="{{ route('hr.attendance.reports.daily.employee-comprehensive', $employee->id) }}" class="report-filters">
                        <div class="row g-2 align-items-start">
                            <div class="col-12 col-md-6 col-lg-3 report-filters-col">
                                <div class="mb-2">
                                    <label class="form-label report-filter-label mb-1" for="hr-employee-date-from">{{ __('hr.date_from') }}</label>
                                    <input type="date" name="date_from" id="hr-employee-date-from" value="{{ $dateFrom }}" class="form-control report-filter-control">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label report-filter-label mb-1" for="hr-employee-date-to">{{ __('hr.date_to') }}</label>
                                    <input type="date" name="date_to" id="hr-employee-date-to" value="{{ $dateTo }}" class="form-control report-filter-control">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3 report-filters-col">
                                <div class="d-flex flex-wrap gap-2 mt-1">
                                    <button type="submit" class="btn btn-sm btn-primaryyy">
                                        <i class="fas fa-filter me-1"></i>{{ __('hr.update_report') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Employee Header Profile Card --}}
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body p-4">
                <div class="row align-items-center g-3">
                    <div class="col-auto">
                        <div class="avatar-circle bg-primary text-white d-flex align-items-center justify-content-center rounded-circle shadow-sm"
                             style="width: 70px; height: 70px; font-size: 32px;">
                            <i class="las la-user-tie"></i>
                        </div>
                    </div>
                    <div class="col">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <h3 class="fw-bold mb-0 text-dark">{{ $employee->name }}</h3>
                            <span class="badge bg-secondary fs-6">#{{ $employee->finger_print_id ?: $employee->id }}</span>
                            @if($employee->status)
                                <span class="badge bg-success-subtle text-success border border-success px-2 py-1">{{ $employee->status }}</span>
                            @endif
                        </div>
                        <div class="row g-3 text-muted mt-1">
                            <div class="col-6 col-md-3">
                                <i class="las la-sitemap text-primary me-1 fs-5"></i>
                                <strong>{{ __('hr.department') }}:</strong> {{ $employee->department?->title ?: '-' }}
                            </div>
                            <div class="col-6 col-md-3">
                                <i class="las la-briefcase text-primary me-1 fs-5"></i>
                                <strong>{{ __('hr.job') }}:</strong> {{ $employee->job?->title ?: '-' }}
                            </div>
                            <div class="col-6 col-md-3">
                                <i class="las la-clock text-primary me-1 fs-5"></i>
                                <strong>{{ __('hr.shift') }}:</strong> {{ $employee->shift?->name ?: '-' }}
                                @if($employee->shift && $employee->shift->start_time)
                                    <small class="d-block text-muted">
                                        ({{ \Carbon\Carbon::parse($employee->shift->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($employee->shift->end_time)->format('h:i A') }})
                                    </small>
                                @endif
                            </div>
                            <div class="col-6 col-md-3">
                                <i class="las la-project-diagram text-primary me-1 fs-5"></i>
                                <strong>{{ __('hr.project_code') }}:</strong> {{ $projectCode ?: '-' }}
                            </div>
                            <div class="col-6 col-md-3">
                                <i class="las la-calendar-check text-primary me-1 fs-5"></i>
                                <strong>{{ __('hr.period') }}:</strong> {{ $dateFrom }} إلى {{ $dateTo }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPIs Summary Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm text-center p-3 bg-white border-bottom border-4 border-success h-100">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('hr.present') }}</div>
                    <div class="fs-2 fw-bold text-success mt-1">{{ $kpis['total_present_days'] }}</div>
                    <div class="small text-muted">{{ __('hr.days') }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm text-center p-3 bg-white border-bottom border-4 border-danger h-100">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('hr.absent') }}</div>
                    <div class="fs-2 fw-bold text-danger mt-1">{{ $kpis['total_absent_days'] }}</div>
                    <div class="small text-muted">{{ __('hr.days') }}</div>
                </div>
            </div>
            {{-- Day Off Used --}}
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm text-center p-3 bg-white border-bottom border-4 h-100"
                     style="border-color:#fd7e14!important">
                    <div class="small fw-bold text-uppercase mb-1" style="color:#fd7e14">
                        <i class="las la-calendar-minus me-1"></i>يوم راحة (Day Off)
                    </div>
                    <div class="fs-2 fw-bold mt-1" style="color:#fd7e14">{{ $kpis['total_day_off_used'] }}</div>
                    <div class="small text-muted">من رصيد {{ $employee->days_off ?? 0 }}</div>
                </div>
            </div>
            {{-- Extra Days --}}
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm text-center p-3 bg-white border-bottom border-4 h-100
                    {{ ($kpis['total_extra_days'] ?? 0) > 0 ? 'border-success' : 'border-light' }}">
                    <div class="small fw-bold text-uppercase mb-1 {{ ($kpis['total_extra_days'] ?? 0) > 0 ? 'text-success' : 'text-muted' }}">
                        <i class="las la-gift me-1"></i>أيام إضافية (Extra)
                    </div>
                    <div class="fs-2 fw-bold mt-1 {{ ($kpis['total_extra_days'] ?? 0) > 0 ? 'text-success' : 'text-muted' }}">
                        {{ $kpis['total_extra_days'] ?? 0 }}
                    </div>
                    <div class="small text-muted">رصيد غير مستخدم</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm text-center p-3 bg-white border-bottom border-4 border-primary h-100">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('hr.work_hours') }}</div>
                    <div class="fs-2 fw-bold text-primary mt-1">{{ number_format($kpis['total_work_hours'], 1) }}</div>
                    <div class="small text-muted">{{ __('hr.hours') }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm text-center p-3 bg-white border-bottom border-4 border-warning h-100">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('hr.late') }}</div>
                    <div class="fs-2 fw-bold text-warning mt-1">{{ number_format($kpis['total_late_minutes'] / 60, 2) }}</div>
                    <div class="small text-muted">{{ __('hr.hours') }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm text-center p-3 bg-white border-bottom border-4 border-info h-100">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('hr.on_leave') }}</div>
                    <div class="fs-2 fw-bold text-info mt-1">{{ $kpis['total_leave_days'] }}</div>
                    <div class="small text-muted">{{ __('hr.days') }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm text-center p-3 bg-white border-bottom border-4 border-secondary h-100">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('hr.total_advances') }}</div>
                    <div class="fs-2 fw-bold text-dark mt-1">{{ number_format($kpis['total_advances'], 2) }}</div>
                    <div class="small text-muted">{{ __('hr.deductions') }}: {{ number_format($kpis['total_deductions'], 2) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm text-center p-3 bg-white border-bottom border-4 border-success h-100">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('hr.monthly_salary') ?? 'الراتب الشهري' }}</div>
                    <div class="fs-2 fw-bold text-success mt-1">
                        {{ number_format((float) ($employee->salary ?? 0), 2) }}
                    </div>
                    <div class="small text-muted">{{ __('hr.currency') ?? currency_symbol() }}</div>
                </div>
            </div>
        </div>

        {{-- ── Hours Summary ─────────────────────────────────────────────── --}}
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
                <i class="las la-hourglass-half text-primary fs-4"></i>
                <h5 class="mb-0 fw-bold">ملخص الساعات</h5>
                <span class="badge bg-light text-muted border ms-auto">{{ $hoursKpis['working_days'] }} يوم عمل</span>
            </div>
            <div class="card-body p-3">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="card border-0 border-bottom border-4 border-secondary bg-light text-center p-3 h-100">
                            <div class="text-secondary small fw-bold text-uppercase mb-1"><i class="las la-clock me-1"></i>المطلوبة</div>
                            <div class="fs-3 fw-bold text-dark font-monospace">{{ $hoursKpis['required_hhmm'] }}</div>
                            <div class="small text-muted mt-1">{{ $hoursKpis['working_days'] }} يوم × الشيفت</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 border-bottom border-4 border-primary text-center p-3 h-100" style="background:rgba(13,110,253,.07)">
                            <div class="text-primary small fw-bold text-uppercase mb-1"><i class="las la-user-clock me-1"></i>الفعلية</div>
                            <div class="fs-3 fw-bold text-primary font-monospace">{{ $hoursKpis['actual_hhmm'] }}</div>
                            @if(($hoursKpis['day_off_used'] ?? 0) > 0)
                                <div class="small mt-1" style="color:#fd7e14">يشمل {{ $hoursKpis['day_off_used'] }} يوم Day Off</div>
                            @else
                                <div class="small text-muted mt-1">ساعة : دقيقة</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 border-bottom border-4 h-100 text-center p-3 {{ $hoursKpis['has_overtime'] ? 'border-success' : 'border-light' }}"
                             style="{{ $hoursKpis['has_overtime'] ? 'background:rgba(25,135,84,.07)' : '' }}">
                            <div class="small fw-bold text-uppercase mb-1 {{ $hoursKpis['has_overtime'] ? 'text-success' : 'text-muted' }}">
                                <i class="las la-plus-circle me-1"></i>الأوفر تايم
                            </div>
                            <div class="fs-3 fw-bold font-monospace {{ $hoursKpis['has_overtime'] ? 'text-success' : 'text-muted' }}">{{ $hoursKpis['overtime_hhmm'] }}</div>
                            <div class="small mt-1 {{ $hoursKpis['has_overtime'] ? 'text-success' : 'text-muted' }}">{{ $hoursKpis['has_overtime'] ? 'ساعات إضافية' : 'لا يوجد إضافي' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 border-bottom border-4 h-100 text-center p-3 {{ $hoursKpis['has_deficit'] ? 'border-danger' : 'border-success' }}"
                             style="{{ $hoursKpis['has_deficit'] ? 'background:rgba(220,53,69,.07)' : 'background:rgba(25,135,84,.07)' }}">
                            <div class="small fw-bold text-uppercase mb-1 {{ $hoursKpis['has_deficit'] ? 'text-danger' : 'text-success' }}">
                                <i class="las la-minus-circle me-1"></i>العجز / المتبقية
                            </div>
                            <div class="fs-3 fw-bold font-monospace {{ $hoursKpis['has_deficit'] ? 'text-danger' : 'text-success' }}">
                                @if($hoursKpis['has_deficit'])- @endif{{ $hoursKpis['deficit_hhmm'] }}
                            </div>
                            <div class="small mt-1 {{ $hoursKpis['has_deficit'] ? 'text-danger' : 'text-success' }}">{{ $hoursKpis['has_deficit'] ? 'عجز في الساعات' : 'لا يوجد عجز' }}</div>
                        </div>
                    </div>
                </div>
                {{-- Progress bar --}}
                @php
                    [$rh, $rm] = explode(':', $hoursKpis['required_hhmm']);
                    [$ah, $am] = explode(':', $hoursKpis['actual_hhmm']);
                    $reqMins  = ((int)$rh * 60) + (int)$rm;
                    $actMins  = ((int)$ah * 60) + (int)$am;
                    $pct      = $reqMins > 0 ? min(100, round(($actMins / $reqMins) * 100)) : 0;
                    $barColor = $pct >= 100 ? 'success' : ($pct >= 75 ? 'warning' : 'danger');
                @endphp
                <div class="mt-3">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>نسبة إنجاز الساعات</span>
                        <span class="fw-bold text-{{ $barColor }}">{{ $pct }}%</span>
                    </div>
                    <div class="progress" style="height:10px;border-radius:6px">
                        <div class="progress-bar bg-{{ $barColor }}" role="progressbar"
                             style="width:{{ $pct }}%" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4 shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="las la-money-bill-wave text-success me-1"></i>{{ __('hr.salary_summary') ?? 'ملخص الراتب للفترة' }}</h5>
            </div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    <div class="col-6 col-md-3">
                        <div class="text-muted small">{{ __('hr.basic_salary') ?? 'الأساسي' }}</div>
                        <div class="fs-5 fw-bold">{{ number_format((float) ($salaryData['basic_salary'] ?? 0), 2) }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small">{{ __('hr.overtime_salary') ?? 'الإضافي' }}</div>
                        <div class="fs-5 fw-bold text-info">{{ number_format((float) ($salaryData['overtime_salary'] ?? 0) + (float) ($salaryData['overtime_days_salary'] ?? 0), 2) }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small">{{ __('hr.deductions') ?? 'الخصومات' }}</div>
                        <div class="fs-5 fw-bold text-danger">{{ number_format($salaryDeductions, 2) }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small">{{ __('hr.net_salary') ?? 'الصافي' }}</div>
                        <div class="fs-5 fw-bold text-success">{{ number_format((float) ($salaryData['total_salary'] ?? 0), 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detailed Navigation Tabs --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white p-0">
                <ul class="nav nav-tabs border-0 px-3 pt-2 flex-nowrap overflow-auto">
                    <li class="nav-item">
                        <button class="nav-link d-flex align-items-center gap-1 px-3 py-2"
                                :class="activeTab === 'attendance' ? 'active fw-bold' : ''"
                                @click="activeTab = 'attendance'">
                            <i class="las la-user-clock fs-5"></i>
                            <span>{{ __('hr.attendance_log') }}</span>
                            <span class="badge bg-primary ms-1">{{ count($dailyRecords) }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link d-flex align-items-center gap-1 px-3 py-2"
                                :class="activeTab === 'leaves' ? 'active fw-bold' : ''"
                                @click="activeTab = 'leaves'">
                            <i class="las la-calendar-times fs-5"></i>
                            <span>{{ __('hr.leaves') }}</span>
                            <span class="badge bg-info ms-1">{{ $leaves->count() }}</span>
                        </button>
                    </li>
                    {{-- Absences / Day-Off tab --}}
                    <li class="nav-item">
                        @php
                            $absentCount = collect($dailyRecords)->where('status', 'absent')->count();
                            $dayOffCount = collect($dailyRecords)->where('status', 'day_off')->count();
                        @endphp
                        <button class="nav-link d-flex align-items-center gap-1 px-3 py-2"
                                :class="activeTab === 'absences' ? 'active fw-bold' : ''"
                                @click="activeTab = 'absences'">
                            <i class="las la-user-times fs-5"></i>
                            <span>الغياب / Day Off</span>
                            @if($absentCount > 0)
                                <span class="badge bg-danger ms-1">{{ $absentCount }}</span>
                            @endif
                            @if($dayOffCount > 0)
                                <span class="badge ms-1" style="background:#fd7e14">{{ $dayOffCount }}</span>
                            @endif
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link d-flex align-items-center gap-1 px-3 py-2"
                                :class="activeTab === 'errands' ? 'active fw-bold' : ''"
                                @click="activeTab = 'errands'">
                            <i class="las la-route fs-5"></i>
                            <span>{{ __('hr.errands') }} / {{ __('hr.with_permission') }}</span>
                            <span class="badge bg-warning text-dark ms-1">{{ $errands->count() + $workPermissions->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link d-flex align-items-center gap-1 px-3 py-2"
                                :class="activeTab === 'advances' ? 'active fw-bold' : ''"
                                @click="activeTab = 'advances'">
                            <i class="las la-money-bill-wave fs-5"></i>
                            <span>{{ __('hr.total_advances') }}</span>
                            <span class="badge bg-success ms-1">{{ $advances->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link d-flex align-items-center gap-1 px-3 py-2"
                                :class="activeTab === 'deductions' ? 'active fw-bold' : ''"
                                @click="activeTab = 'deductions'">
                            <i class="las la-balance-scale fs-5"></i>
                            <span>{{ __('hr.total_deductions') }} / {{ __('hr.total_rewards') }}</span>
                            <span class="badge bg-danger ms-1">{{ $deductionsRewards->count() }}</span>
                        </button>
                    </li>
                    @if($flexibleSalaryProcessings->isNotEmpty())
                        <li class="nav-item">
                            <button class="nav-link d-flex align-items-center gap-1 px-3 py-2"
                                    :class="activeTab === 'flex' ? 'active fw-bold' : ''"
                                    @click="activeTab = 'flex'">
                                <i class="las la-calculator fs-5"></i>
                                <span>{{ __('hr.flexible_salary_processing') }}</span>
                                <span class="badge bg-secondary ms-1">{{ $flexibleSalaryProcessings->count() }}</span>
                            </button>
                        </li>
                    @endif
                </ul>
            </div>

            <div class="card-body p-0">
                {{-- 1. Attendance Log Tab --}}
                <div x-show="activeTab === 'attendance'" x-cloak>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted">
                                <tr>
                                    <th class="px-4 py-3">#</th>
                                    <th class="py-3">{{ __('hr.date') }}</th>
                                    <th class="py-3">{{ __('hr.status') }}</th>
                                    <th class="py-3">{{ __('hr.project_code') }}</th>
                                    <th class="py-3 text-center">{{ __('hr.check_in_time') }}</th>
                                    <th class="py-3 text-center">{{ __('hr.check_out_time') }}</th>
                                    <th class="py-3 text-center">{{ __('hr.work_hours') }}</th>
                                    <th class="py-3 text-center">{{ __('hr.late') }}</th>
                                    <th class="py-3 text-center">{{ __('hr.early_departure_minutes') }}</th>
                                    <th class="py-3">{{ __('hr.notes') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dailyRecords as $index => $rec)
                                    <tr class="
                                        {{ $rec['status'] === 'absent'  ? 'table-danger-subtle'  : '' }}
                                        {{ $rec['status'] === 'weekend' ? 'table-light text-muted' : '' }}
                                        {{ $rec['status'] === 'day_off' ? 'table-warning-subtle'  : '' }}
                                    ">
                                        <td class="px-4 py-3 text-muted">{{ $index + 1 }}</td>
                                        <td class="py-3">
                                            <strong>{{ $rec['date'] }}</strong>
                                            <small class="text-muted d-block">{{ $rec['day_name'] }}</small>
                                        </td>
                                        <td class="py-3">
                                            @if($rec['status'] === 'present')
                                                <span class="badge bg-success-subtle text-success border border-success">{{ __('hr.present') }}</span>
                                            @elseif($rec['status'] === 'absent')
                                                <span class="badge bg-danger-subtle text-danger border border-danger">{{ __('hr.absent') }}</span>
                                            @elseif($rec['status'] === 'day_off')
                                                <span class="badge border fw-semibold" style="background:rgba(253,126,20,.12);color:#fd7e14;border-color:#fd7e14!important">
                                                    <i class="las la-calendar-minus me-1"></i>Day Off
                                                </span>
                                            @elseif($rec['status'] === 'leave')
                                                <span class="badge bg-info-subtle text-info border border-info">{{ __('hr.on_leave') }}</span>
                                            @elseif($rec['status'] === 'errand')
                                                <span class="badge bg-warning-subtle text-warning border border-warning">{{ __('hr.on_errand') }}</span>
                                            @elseif($rec['status'] === 'permission')
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary">{{ __('hr.with_permission') }}</span>
                                            @elseif($rec['status'] === 'weekend')
                                                <span class="badge bg-light text-muted border">{{ __('hr.weekend') ?? 'عطلة' }}</span>
                                            @endif
                                        </td>
                                        <td class="py-3">
                                            @if(!empty($rec['project_code']) && $rec['project_code'] !== '-')
                                                <span class="badge bg-primary-subtle text-primary fw-bold">{{ $rec['project_code'] }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="py-3 text-center font-monospace">
                                            {{ $rec['check_in'] ? \Carbon\Carbon::parse($rec['check_in'])->format('h:i A') : '-' }}
                                        </td>
                                        <td class="py-3 text-center font-monospace">
                                            {{ $rec['check_out'] ? \Carbon\Carbon::parse($rec['check_out'])->format('h:i A') : '-' }}
                                        </td>
                                        <td class="py-3 text-center fw-bold font-monospace">
                                            @php
                                                $wm = (int) ($rec['work_minutes'] ?? round(($rec['work_hours'] ?? 0) * 60));
                                            @endphp
                                            {{ $wm > 0 ? sprintf('%02d:%02d', intdiv($wm, 60), $wm % 60) : '-' }}
                                        </td>
                                        <td class="py-3 text-center">
                                            @if($rec['late_minutes'] > 0)
                                                @php $lm = (int) $rec['late_minutes']; @endphp
                                                <span class="badge bg-danger font-monospace">{{ sprintf('%02d:%02d', intdiv($lm, 60), $lm % 60) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="py-3 text-center">
                                            @if($rec['early_minutes'] > 0)
                                                @php $em = (int) $rec['early_minutes']; @endphp
                                                <span class="badge bg-warning text-dark font-monospace">{{ sprintf('%02d:%02d', intdiv($em, 60), $em % 60) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="py-3 small text-muted">{{ $rec['notes'] ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-5 text-muted">{{ __('hr.no_records_found') ?? 'لا توجد سجلات' }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Absences / Day-Off Tab --}}
                <div x-show="activeTab === 'absences'" x-cloak>
                    @php
                        $absentDays = collect($dailyRecords)->where('status', 'absent')->values();
                        $dayOffDays = collect($dailyRecords)->where('status', 'day_off')->values();
                        $extraDays  = $kpis['total_extra_days'] ?? 0;
                    @endphp

                    {{-- Summary row --}}
                    <div class="d-flex flex-wrap gap-3 p-3 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-danger fs-6">{{ $absentDays->count() }}</span>
                            <span class="text-muted small">يوم غياب فعلي</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge fs-6" style="background:#fd7e14">{{ $dayOffDays->count() }}</span>
                            <span class="text-muted small">يوم راحة (Day Off مستخدم)</span>
                        </div>
                        @if($extraDays > 0)
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success fs-6">{{ $extraDays }}</span>
                            <span class="text-muted small">يوم إضافي (رصيد غير مستخدم)</span>
                        </div>
                        @endif
                        <div class="ms-auto text-muted small d-flex align-items-center gap-1">
                            <i class="las la-info-circle"></i>
                            رصيد الـ Day Off الكلي: <strong>{{ $employee->days_off ?? 0 }}</strong>
                        </div>
                    </div>

                    {{-- Day Off rows --}}
                    @if($dayOffDays->isNotEmpty())
                    <div class="p-3 pb-0">
                        <h6 class="fw-bold mb-2" style="color:#fd7e14">
                            <i class="las la-calendar-minus me-1"></i>أيام الراحة المستخدمة (Day Off)
                        </h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead style="background:rgba(253,126,20,.08)">
                                <tr>
                                    <th class="px-4 py-2">#</th>
                                    <th class="py-2">التاريخ</th>
                                    <th class="py-2">اليوم</th>
                                    <th class="py-2 text-center">الحالة</th>
                                    <th class="py-2">ملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dayOffDays as $i => $rec)
                                <tr style="background:rgba(253,126,20,.04)">
                                    <td class="px-4 py-2 text-muted">{{ $i + 1 }}</td>
                                    <td class="py-2 fw-bold">{{ $rec['date'] }}</td>
                                    <td class="py-2 text-muted">{{ $rec['day_name'] }}</td>
                                    <td class="py-2 text-center">
                                        <span class="badge border" style="background:rgba(253,126,20,.12);color:#fd7e14;border-color:#fd7e14!important">
                                            <i class="las la-calendar-minus me-1"></i>Day Off
                                        </span>
                                    </td>
                                    <td class="py-2 small text-muted">{{ $rec['notes'] ?: 'يوم راحة من الرصيد' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    {{-- Absent rows --}}
                    @if($absentDays->isNotEmpty())
                    <div class="p-3 pb-0 {{ $dayOffDays->isNotEmpty() ? 'mt-3 border-top' : '' }}">
                        <h6 class="fw-bold mb-2 text-danger">
                            <i class="las la-user-times me-1"></i>أيام الغياب الفعلي
                        </h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="bg-danger bg-opacity-10">
                                <tr>
                                    <th class="px-4 py-2">#</th>
                                    <th class="py-2">التاريخ</th>
                                    <th class="py-2">اليوم</th>
                                    <th class="py-2 text-center">الحالة</th>
                                    <th class="py-2">ملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($absentDays as $i => $rec)
                                <tr class="table-danger-subtle">
                                    <td class="px-4 py-2 text-muted">{{ $i + 1 }}</td>
                                    <td class="py-2 fw-bold">{{ $rec['date'] }}</td>
                                    <td class="py-2 text-muted">{{ $rec['day_name'] }}</td>
                                    <td class="py-2 text-center">
                                        <span class="badge bg-danger">{{ __('hr.absent') }}</span>
                                    </td>
                                    <td class="py-2 small text-muted">{{ $rec['notes'] ?: '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    @if($absentDays->isEmpty() && $dayOffDays->isEmpty())
                        <div class="text-center py-5">
                            <i class="las la-check-circle fs-1 text-success"></i>
                            <p class="text-muted mt-2 mb-0">لا توجد أيام غياب ولا Day Off في هذه الفترة</p>
                        </div>
                    @endif
                </div>

                {{-- 2. Leaves Tab --}}
                <div x-show="activeTab === 'leaves'" x-cloak>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted">
                                <tr>
                                    <th class="px-4 py-3">#</th>
                                    <th class="py-3">{{ __('hr.leave_type') }}</th>
                                    <th class="py-3">{{ __('hr.leave_start_date') }}</th>
                                    <th class="py-3">{{ __('hr.leave_end_date') }}</th>
                                    <th class="py-3 text-center">{{ __('hr.leave_days_count') }}</th>
                                    <th class="py-3 text-center">{{ __('hr.paid') }}</th>
                                    <th class="py-3">{{ __('hr.leave_reason') }}</th>
                                    <th class="py-3 text-center">{{ __('hr.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($leaves as $i => $leave)
                                    <tr>
                                        <td class="px-4 py-3 text-muted">{{ $i + 1 }}</td>
                                        <td class="py-3 fw-bold">{{ $leave->leaveType?->name ?: '-' }}</td>
                                        <td class="py-3">{{ $leave->start_date ? \Carbon\Carbon::parse($leave->start_date)->format('Y-m-d') : '-' }}</td>
                                        <td class="py-3">{{ $leave->end_date ? \Carbon\Carbon::parse($leave->end_date)->format('Y-m-d') : '-' }}</td>
                                        <td class="py-3 text-center"><span class="badge bg-primary">{{ $leave->duration_days }} {{ __('hr.days') }}</span></td>
                                        <td class="py-3 text-center">
                                            @if($leave->leaveType?->is_paid)
                                                <span class="badge bg-success">{{ __('hr.paid') }}</span>
                                            @else
                                                <span class="badge bg-warning text-dark">{{ __('hr.unpaid') }}</span>
                                            @endif
                                        </td>
                                        <td class="py-3 small">{{ $leave->reason ?: '-' }}</td>
                                        <td class="py-3 text-center">
                                            <span class="badge bg-{{ $leave->status === 'approved' ? 'success' : ($leave->status === 'rejected' ? 'danger' : 'warning text-dark') }}">
                                                {{ $leave->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">{{ __('hr.no_employees_on_leave') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- 3. Errands & Work Permissions Tab --}}
                <div x-show="activeTab === 'errands'" x-cloak class="p-4">
                    <h5 class="fw-bold mb-3 text-primary"><i class="las la-route me-1"></i>{{ __('hr.errands') }}</h5>
                    <div class="table-responsive mb-4">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted">
                                <tr>
                                    <th class="px-4 py-3">#</th>
                                    <th class="py-3">{{ __('hr.title') }}</th>
                                    <th class="py-3">{{ __('hr.start_date') }}</th>
                                    <th class="py-3">{{ __('hr.end_date') }}</th>
                                    <th class="py-3">{{ __('hr.location') ?? 'الوجهة' }}</th>
                                    <th class="py-3 text-center">{{ __('hr.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($errands as $i => $errand)
                                    <tr>
                                        <td class="px-4 py-3 text-muted">{{ $i + 1 }}</td>
                                        <td class="py-3 fw-bold">{{ $errand->title }}</td>
                                        <td class="py-3">{{ $errand->start_date ? \Carbon\Carbon::parse($errand->start_date)->format('Y-m-d') : '-' }}</td>
                                        <td class="py-3">{{ $errand->end_date ? \Carbon\Carbon::parse($errand->end_date)->format('Y-m-d') : '-' }}</td>
                                        <td class="py-3">{{ $errand->location ?: '-' }}</td>
                                        <td class="py-3 text-center">
                                            @if($errand->approved_at)
                                                <span class="badge bg-success">{{ __('hr.approved') ?? 'معتمد' }}</span>
                                            @else
                                                <span class="badge bg-warning text-dark">{{ __('hr.pending') ?? 'معلق' }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">{{ __('hr.no_employees_on_errand') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h5 class="fw-bold mb-3 text-primary"><i class="las la-user-clock me-1"></i>{{ __('hr.with_permission') }}</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted">
                                <tr>
                                    <th class="px-4 py-3">#</th>
                                    <th class="py-3">{{ __('hr.date') }}</th>
                                    <th class="py-3">{{ __('hr.reason') }}</th>
                                    <th class="py-3 text-center">{{ __('hr.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($workPermissions as $i => $perm)
                                    <tr>
                                        <td class="px-4 py-3 text-muted">{{ $i + 1 }}</td>
                                        <td class="py-3">{{ $perm->date ? $perm->date->format('Y-m-d') : '-' }}</td>
                                        <td class="py-3">{{ $perm->reason ?: '-' }}</td>
                                        <td class="py-3 text-center">
                                            <span class="badge bg-{{ $perm->status === 'approved' ? 'success' : ($perm->status === 'rejected' ? 'danger' : 'warning text-dark') }}">
                                                {{ $perm->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">{{ __('hr.no_employees_with_permission') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- 4. Advances Tab --}}
                <div x-show="activeTab === 'advances'" x-cloak>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted">
                                <tr>
                                    <th class="px-4 py-3">#</th>
                                    <th class="py-3">{{ __('hr.date') }}</th>
                                    <th class="py-3">{{ __('hr.amount') }}</th>
                                    <th class="py-3 text-center">{{ __('hr.status') }}</th>
                                    <th class="py-3 text-center">{{ __('hr.deducted_from_salary') ?? 'خصم من الراتب' }}</th>
                                    <th class="py-3">{{ __('hr.notes') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($advances as $i => $adv)
                                    <tr>
                                        <td class="px-4 py-3 text-muted">{{ $i + 1 }}</td>
                                        <td class="py-3">{{ $adv->date ? $adv->date->format('Y-m-d') : '-' }}</td>
                                        <td class="py-3 fw-bold text-success">{{ number_format((float) $adv->amount, 2) }}</td>
                                        <td class="py-3 text-center">
                                            <span class="badge bg-{{ $adv->status === 'approved' ? 'success' : ($adv->status === 'rejected' ? 'danger' : 'warning text-dark') }}">
                                                {{ $adv->status }}
                                            </span>
                                        </td>
                                        <td class="py-3 text-center">
                                            @if($adv->deducted_from_salary)
                                                <span class="badge bg-success">{{ __('hr.yes') ?? 'نعم' }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('hr.no') ?? 'لا' }}</span>
                                            @endif
                                        </td>
                                        <td class="py-3 small">{{ $adv->notes ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">{{ __('hr.no_records_found') ?? 'لا توجد سلف' }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- 5. Deductions & Rewards Tab --}}
                <div x-show="activeTab === 'deductions'" x-cloak>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted">
                                <tr>
                                    <th class="px-4 py-3">#</th>
                                    <th class="py-3">{{ __('hr.type') }}</th>
                                    <th class="py-3">{{ __('hr.date') }}</th>
                                    <th class="py-3">{{ __('hr.amount') }}</th>
                                    <th class="py-3">{{ __('hr.reason') }}</th>
                                    <th class="py-3 text-center">{{ __('hr.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($deductionsRewards as $i => $dr)
                                    <tr>
                                        <td class="px-4 py-3 text-muted">{{ $i + 1 }}</td>
                                        <td class="py-3">
                                            @if($dr->type === 'deduction')
                                                <span class="badge bg-danger">{{ __('hr.deduction') ?? 'خصم' }}</span>
                                            @else
                                                <span class="badge bg-success">{{ __('hr.reward') ?? 'مكافأة' }}</span>
                                            @endif
                                        </td>
                                        <td class="py-3">{{ $dr->date ? $dr->date->format('Y-m-d') : '-' }}</td>
                                        <td class="py-3 fw-bold {{ $dr->type === 'deduction' ? 'text-danger' : 'text-success' }}">
                                            {{ number_format((float) $dr->amount, 2) }}
                                        </td>
                                        <td class="py-3 small">{{ $dr->reason ?: '-' }}</td>
                                        <td class="py-3 text-center">
                                            <span class="badge bg-{{ $dr->status === 'approved' ? 'success' : ($dr->status === 'rejected' ? 'danger' : 'warning text-dark') }}">
                                                {{ $dr->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">{{ __('hr.no_records_found') ?? 'لا توجد خصومات أو مكافآت' }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- 6. Flexible Salary Tab --}}
                @if($flexibleSalaryProcessings->isNotEmpty())
                    <div x-show="activeTab === 'flex'" x-cloak>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light text-muted">
                                    <tr>
                                        <th class="px-4 py-3">#</th>
                                        <th class="py-3">{{ __('hr.period') }}</th>
                                        <th class="py-3">{{ __('hr.fixed_salary') ?? 'الراتب الثابت' }}</th>
                                        <th class="py-3">{{ __('hr.hours_worked') ?? 'ساعات العمل' }}</th>
                                        <th class="py-3">{{ __('hr.hourly_wage') ?? 'أجر الساعة' }}</th>
                                        <th class="py-3">{{ __('hr.total_salary') ?? 'إجمالي الراتب' }}</th>
                                        <th class="py-3 text-center">{{ __('hr.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($flexibleSalaryProcessings as $i => $flex)
                                        <tr>
                                            <td class="px-4 py-3 text-muted">{{ $i + 1 }}</td>
                                            <td class="py-3">{{ $flex->period_start?->format('Y-m-d') }} — {{ $flex->period_end?->format('Y-m-d') }}</td>
                                            <td class="py-3">{{ number_format((float) $flex->fixed_salary, 2) }}</td>
                                            <td class="py-3">{{ number_format((float) $flex->hours_worked, 2) }}</td>
                                            <td class="py-3">{{ number_format((float) $flex->hourly_wage, 2) }}</td>
                                            <td class="py-3 fw-bold text-success">{{ number_format((float) $flex->total_salary, 2) }}</td>
                                            <td class="py-3 text-center">{!! $flex->status_badge !!}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>
    </x-reports::report-page>

    <style>
        [x-cloak] { display: none !important; }
        .nav-tabs .nav-link { border-radius: 0; border: none; border-bottom: 3px solid transparent; color: #6c757d; white-space: nowrap; }
        .nav-tabs .nav-link.active { border-bottom-color: #0d6efd; color: #0d6efd; background: transparent; }
        .nav-tabs .nav-link:hover:not(.active) { border-bottom-color: #dee2e6; color: #495057; }
        @media print {
            .btn, .breadcrumb, form { display: none !important; }
            .card { box-shadow: none !important; border: 1px solid #ddd !important; }
            [x-cloak] { display: block !important; }
            .tab-content > div { display: block !important; }
        }
    </style>
@endsection
