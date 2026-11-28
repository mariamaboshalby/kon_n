@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('hr::hr-management.employees.partials.form.form-layout', [
        'title' => __('hr::hr.add_employee'),
        'isEdit' => false,
        'formAction' => route('employees.store'),
        'formMethod' => 'POST',
        'employee' => null,
        'formPayload' => null,
        'salary_basic_accounts' => $salary_basic_accounts,
        'countries' => $countries,
        'states' => $states,
        'cities' => $cities,
        'towns' => $towns,
        'departments' => $departments,
        'jobs' => $jobs,
        'shifts' => $shifts,
        'kpis' => $kpis,
        'leaveTypes' => $leaveTypes,
        'employees' => $employees,
        'currencies' => $currencies ?? null,
        'defaultCurrency' => $defaultCurrency ?? null,
    ])
@endsection
