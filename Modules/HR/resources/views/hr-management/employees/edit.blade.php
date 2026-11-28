@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('hr::hr-management.employees.partials.form.form-layout', [
        'title' => __('hr::hr.edit_employee'),
        'isEdit' => true,
        'formAction' => route('employees.update', $employee->id),
        'formMethod' => 'PUT',
        'employee' => $employee,
        'formPayload' => $formPayload,
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
