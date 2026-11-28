@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
<x-reports::report-page
        :title="__('hr::hr.project_attendance_report')"
        :breadcrumbs="[
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.attendances')],
            ['label' => __('hr::hr.project_attendance_report')],
        ]"
        filter-collapse-id="hr-project-filters-collapse"
     :with-card="false">
        <div class="collapse mb-3 report-filters-collapse" id="hr-project-filters-collapse">
            <div class="card mb-0">
                <div class="card-body">
                    <form method="GET" action="{{ route('hr.attendance.reports.project') }}" class="report-filters">
                        <div class="row g-2 align-items-start">
                            <div class="col-12 col-md-6 col-lg-3 report-filters-col">
                                <div class="mb-2">
                                    <label class="form-label report-filter-label mb-1" for="hr-project-start">{{ __('hr::hr.date_from') }}</label>
                                    <input type="date" name="start_date" id="hr-project-start" value="{{ $startDate }}" class="form-control report-filter-control">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label report-filter-label mb-1" for="hr-project-end">{{ __('hr::hr.date_to') }}</label>
                                    <input type="date" name="end_date" id="hr-project-end" value="{{ $endDate }}" class="form-control report-filter-control">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3 report-filters-col">
                                <div class="mb-2">
                                    <label class="form-label report-filter-label mb-1" for="hr-project-code">{{ __('hr::hr.project') }}</label>
                                    <select name="project_code" id="hr-project-code" class="form-select report-filter-control">
                                        <option value="">{{ __('hr::hr.all') }}</option>
                                        @foreach ($projectCodes as $code)
                                            <option value="{{ $code }}" @selected($projectCode === $code)>{{ $code }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label report-filter-label mb-1" for="hr-project-department">{{ __('hr::hr.department') }}</label>
                                    <select name="department_id" id="hr-project-department" class="form-select report-filter-control">
                                        <option value="">{{ __('hr::hr.all_departments') }}</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}" @selected((int) $departmentId === (int) $department->id)>{{ $department->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3 report-filters-col">
                                <div class="mb-0">
                                    <label class="form-label report-filter-label mb-1" for="hr-project-employee">{{ __('hr::hr.employee') }}</label>
                                    <select name="employee_id" id="hr-project-employee" class="form-select report-filter-control js-searchable-filter">
                                        <option value="">{{ __('hr::hr.all_employees') }}</option>
                                        @foreach ($employees as $employee)
                                            <option value="{{ $employee->id }}" @selected((int) $employeeId === (int) $employee->id)>{{ $employee->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3 report-filters-col">
                                <div class="d-flex flex-wrap gap-2 mt-1">
                                    <button type="submit" class="btn btn-sm btn-primaryyy">
                                        <i class="fas fa-filter me-1"></i>{{ __('hr::hr.generate_report') }}
                                    </button>
                                    <a href="{{ route('hr.attendance.reports.project') }}" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-redo me-1"></i>{{ __('reports::reports.reset') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="alert alert-light border mb-0">{{ __('hr::hr.total_hours') }}: <strong>{{ number_format($summary['total_hours'], 2) }}</strong></div>
            </div>
            <div class="col-md-4">
                <div class="alert alert-light border mb-0">{{ __('hr::hr.overtime_minutes') }}: <strong>{{ number_format($summary['total_overtime_minutes'], 0) }}</strong></div>
            </div>
            <div class="col-md-4">
                <div class="alert alert-light border mb-0">{{ __('hr::hr.rows_count') }}: <strong>{{ $summary['rows_count'] }}</strong></div>
            </div>
        </div>

        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-striped text-center mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('hr::hr.date') }}</th>
                            <th>{{ __('hr::hr.employee') }}</th>
                            <th>{{ __('hr::hr.department') }}</th>
                            <th>{{ __('hr::hr.project') }}</th>
                            <th>{{ __('hr::hr.hours') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $idx => $row)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>{{ optional($row->attendance_date)->format('Y-m-d') ?? $row->attendance_date }}</td>
                                <td>{{ $row->employee->name ?? '-' }}</td>
                                <td>{{ $row->department->title ?? '-' }}</td>
                                <td>{{ $row->project_code ?? '-' }}</td>
                                <td>{{ $row->attendance_actual_hours_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">{{ __('hr::hr.no_data') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-reports::report-page>
@endsection
