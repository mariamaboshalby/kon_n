@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.create_new_processing'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.flexible_salary_processing'), 'url' => route('hr.flexible-salary.processing.index')],
            ['label' => __('hr::hr.create_new_processing')],
        ],
    ])

    <div style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrFlexAlert" class="alert d-none" role="alert"></div>
        <div class="card">
            <div class="card-body">
                <form id="hrFlexCreateForm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('hr::hr.processing_type') }}</label>
                            <select name="processing_type" id="hrFlexType" class="form-select">
                                <option value="single">{{ __('hr::hr.single_employee') }}</option>
                                <option value="department">{{ __('hr::hr.department') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="hrFlexEmpWrap">
                            <label class="form-label">{{ __('hr::hr.employee') }}</label>
                            <select name="selected_employee" id="hrFlexEmployee" class="form-select js-searchable-filter">
                                <option value="">{{ __('hr::hr.select_employee') }}</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 d-none" id="hrFlexDeptWrap">
                            <label class="form-label">{{ __('hr::hr.department') }}</label>
                            <select name="selected_department" id="hrFlexDepartment" class="form-select">
                                <option value="">{{ __('hr::hr.select_department') }}</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('hr::hr.date_from') }}</label>
                            <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('hr::hr.date_to') }}</label>
                            <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('hr::hr.notes') }}</label>
                            <input type="text" name="notes" class="form-control">
                        </div>
                    </div>
                    <div class="table-responsive mt-3">
                        <table class="table table-bordered">
                            <thead><tr><th>{{ __('hr::hr.employee') }}</th><th>{{ __('hr::hr.hours_worked') }}</th></tr></thead>
                            <tbody id="hrFlexHoursBody"></tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-main" id="hrFlexSubmitBtn">{{ __('hr::hr.save') }}</button>
                    <a href="{{ route('hr.flexible-salary.processing.index') }}" class="btn btn-secondary">{{ __('hr::hr.cancel') }}</a>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window._hrFlexibleSalaryConfig = {
        mode: 'create',
        routes: {
            store: @json(route('hr.flexible-salary.processing.store')),
            employees: @json(route('hr.flexible-salary.processing.employees'))
        },
        employees: @json($employees),
        csrfToken: @json(csrf_token()),
        trans: { hours: @json(__('hr::hr.hours_worked')) }
    };
</script>
<script src="{{ asset('js/hr-flexible-salary.js') }}"></script>
@endpush
