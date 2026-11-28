<?php

declare(strict_types=1);

namespace Modules\HR\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $routeEmployee = $this->route('employee');
        $employeeId = is_object($routeEmployee)
            ? ($routeEmployee->id ?? null)
            : ($routeEmployee ?: ($this->input('employeeId') ?: null));

        return [
            'name' => ['required', 'string', 'unique:employees,name,'.$employeeId],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255', 'unique:employees,code,'.$employeeId],
            'email' => ['nullable', 'email', 'unique:employees,email,'.$employeeId],
            'phone' => ['nullable', 'string', 'unique:employees,phone,'.$employeeId],
            'image' => ['nullable', 'file', 'max:2048'],
            'gender' => ['nullable', 'in:male,female'],
            'date_of_birth' => ['nullable', 'date'],
            'nationalId' => ['nullable', 'string', 'unique:employees,nationalId,'.$employeeId],
            'marital_status' => ['nullable', 'in:single,married,divorced,widowed,غير متزوج,متزوج,مطلق,أرمل'],
            'education' => ['nullable', 'in:diploma,bachelor,master,doctorate,دبلوم,بكالوريوس,ماجستير,دكتوراه'],
            'information' => ['nullable', 'string'],
            'status' => ['nullable', 'in:مقيم,مواطن,زائر,خارج الشركة,resident,citizen,visitor,outside_company'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'state_id' => ['nullable', 'exists:states,id'],
            'town_id' => ['nullable', 'exists:towns,id'],
            'job_id' => ['nullable', 'exists:employees_jobs,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'department_director_id' => ['nullable', 'exists:employees,id'],
            'date_of_hire' => ['nullable', 'date'],
            'date_of_joining' => ['nullable', 'date'],
            'date_of_fire' => ['nullable', 'date'],
            'job_level' => ['nullable', 'string'],
            'salary' => ['nullable', 'numeric'],
            'currency_id' => ['nullable', 'exists:currencies,id'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.00000001'],
            'currency_salary' => ['nullable', 'numeric', 'min:0'],
            'finger_print_id' => ['nullable', 'integer', 'unique:employees,finger_print_id,'.$employeeId],
            'finger_print_name' => ['nullable', 'string', 'unique:employees,finger_print_name,'.$employeeId],
            'salary_type' => ['nullable', 'string'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'password' => ['nullable', 'string', 'min:6'],
            'additional_hour_calculation' => ['nullable', 'numeric'],
            'additional_day_calculation' => ['nullable', 'numeric'],
            'late_hour_calculation' => ['nullable', 'numeric'],
            'late_day_calculation' => ['nullable', 'numeric'],
            'flexible_hourly_wage' => ['nullable', 'numeric'],
            'allowed_permission_days' => ['nullable', 'integer', 'min:0'],
            'allowed_late_days' => ['nullable', 'integer', 'min:0'],
            'allowed_absent_days' => ['nullable', 'integer', 'min:0'],
            'days_off' => ['nullable', 'integer', 'min:0'],
            'is_errand_allowed' => ['nullable', 'boolean'],
            'allowed_errand_days' => ['nullable', 'integer', 'min:0'],
            'line_manager_id' => ['nullable', 'exists:employees,id'],
            'require_location' => ['nullable', 'boolean'],
            'location_type'    => ['nullable', 'in:company,project'],
            'kpi_ids' => ['nullable', 'array'],
            'kpi_ids.*' => ['exists:kpis,id'],
            'kpi_weights' => ['nullable', 'array'],
            'kpi_weights.*' => ['nullable', 'integer', 'min:0', 'max:100'],
            'selected_kpi_id' => ['nullable', 'exists:kpis,id'],
            'salary_basic_account_id' => ['nullable', 'exists:acc_head,id'],
            'opening_balance' => ['nullable', 'numeric'],
            'leave_balances' => ['nullable', 'array'],
            'leave_balances.*.leave_type_id' => ['required', 'exists:leave_types,id'],
            'leave_balances.*.year' => ['required', 'integer', 'min:2020', 'max:2030'],
            'leave_balances.*.opening_balance_days' => ['nullable', 'numeric', 'min:0'],
            'leave_balances.*.used_days' => ['nullable', 'numeric', 'min:0'],
            'leave_balances.*.pending_days' => ['nullable', 'numeric', 'min:0'],
            'leave_balances.*.max_monthly_days' => ['required', 'numeric', 'min:0'],
            'leave_balances.*.notes' => ['nullable', 'string'],
            'selected_leave_type_id' => ['nullable', 'exists:leave_types,id'],
            'national_id_number' => ['nullable', 'string', 'max:50'],
            'national_id_start'  => ['nullable', 'date'],
            'national_id_expiry' => ['nullable', 'date'],
            'residence_number'   => ['nullable', 'string', 'max:50'],
            'residence_start'    => ['nullable', 'date'],
            'residence_expiry'   => ['nullable', 'date'],
            'license_number'     => ['nullable', 'string', 'max:50'],
            'license_start'      => ['nullable', 'date'],
            'license_expiry'     => ['nullable', 'date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Name validation
            'name.required' => __('hr.name_required'),
            'name.string' => __('hr.name_string'),
            'name.unique' => __('hr.name_unique'),

            // English name validation
            'name_en.string' => __('hr.name_string'),
            'name_en.max' => __('hr.name_string'),

            // Nickname validation
            'name_ar.string' => __('hr.name_string'),
            'name_ar.max' => __('hr.name_string'),

            // Employee code validation
            'code.string' => __('hr.name_string'),
            'code.max' => __('hr.name_string'),
            'code.unique' => __('hr.name_unique'),

            // Email validation
            'email.required' => __('hr.email_required'),
            'email.email' => __('hr.email_email'),
            'email.unique' => __('hr.email_unique'),

            // Phone validation
            'phone.required' => __('hr.phone_required'),
            'phone.string' => __('hr.phone_string'),
            'phone.unique' => __('hr.phone_unique'),

            // File upload validation
            'image.file' => __('hr.image_image'),
            'image.max' => __('hr.image_max'),

            // Gender validation
            'gender.in' => __('hr.gender_invalid'),

            // Date of birth validation
            'date_of_birth.date' => __('hr.date_of_birth_date'),

            // National ID validation
            'nationalId.string' => __('hr.nationalId_string'),
            'nationalId.unique' => __('hr.nationalId_unique'),

            // Marital status validation
            'marital_status.in' => __('hr.marital_status_invalid'),

            // Education validation
            'education.in' => __('hr.education_invalid'),

            // Information validation
            'information.string' => __('hr.information_string'),

            // Status validation
            'status.required' => __('hr.status_required'),
            'status.in' => __('hr.status_invalid'),

            // Location validation
            'country_id.exists' => __('hr.country_id_exists'),
            'city_id.exists' => __('hr.city_id_exists'),
            'state_id.exists' => __('hr.state_id_exists'),
            'town_id.exists' => __('hr.town_id_exists'),

            // Job and department validation
            'job_id.exists' => __('hr.job_id_exists'),
            'department_id.exists' => __('hr.department_id_exists'),

            // Date validation
            'date_of_hire.date' => __('hr.date_of_hire_date'),
            'date_of_joining.date' => __('hr.date_of_joining_date'),
            'date_of_fire.date' => __('hr.date_of_fire_date'),

            // Job level validation
            'job_level.string' => __('hr.job_level_string'),

            // Salary validation
            'salary.numeric' => __('hr.salary_numeric'),
            'salary_type.string' => __('hr.salary_type_string'),

            // Fingerprint validation
            'finger_print_id.integer' => __('hr.finger_print_id_integer'),
            'finger_print_id.unique' => __('hr.finger_print_id_unique'),
            'finger_print_name.string' => __('hr.finger_print_name_string'),
            'finger_print_name.unique' => __('hr.finger_print_name_unique'),

            // Shift validation
            'shift_id.exists' => __('hr.shift_id_exists'),

            // Password validation
            'password.required' => __('hr.password_required'),
            'password.string' => __('hr.password_string'),
            'password.min' => __('hr.password_min'),

            // Calculation validation
            'additional_hour_calculation.numeric' => __('hr.additional_hour_calculation_numeric'),
            'additional_day_calculation.numeric' => __('hr.additional_day_calculation_numeric'),
            'late_hour_calculation.numeric' => __('hr.late_hour_calculation_numeric'),
            'late_day_calculation.numeric' => __('hr.late_day_calculation_numeric'),
            'flexible_hourly_wage.numeric' => __('hr.flexible_hourly_wage_numeric'),

            // Attendance permissions validation
            'allowed_permission_days.integer' => __('hr.allowed_permission_days_integer'),
            'allowed_permission_days.min' => __('hr.allowed_permission_days_min'),
            'allowed_late_days.integer' => __('hr.allowed_late_days_integer'),
            'allowed_late_days.min' => __('hr.allowed_late_days_min'),
            'allowed_absent_days.integer' => __('hr.allowed_absent_days_integer'),
            'allowed_absent_days.min' => __('hr.allowed_absent_days_min'),
            'days_off.integer' => __('hr.days_off_integer'),
            'days_off.min' => __('hr.days_off_min'),
            'is_errand_allowed.boolean' => __('hr.is_errand_allowed_boolean'),
            'allowed_errand_days.integer' => __('hr.allowed_errand_days_integer'),
            'allowed_errand_days.min' => __('hr.allowed_errand_days_min'),
            'line_manager_id.exists' => __('hr.line_manager_id_exists'),

            // KPI validation
            'kpi_ids.array' => __('hr.kpi_ids_array'),
            'kpi_ids.*.exists' => __('hr.kpi_ids_exists'),
            'kpi_weights.array' => __('hr.kpi_weights_array'),
            'kpi_weights.*.integer' => __('hr.kpi_weights_integer'),
            'kpi_weights.*.min' => __('hr.kpi_weights_min'),
            'kpi_weights.*.max' => __('hr.kpi_weights_max'),
            'selected_kpi_id.exists' => __('hr.selected_kpi_id_exists'),

            // Account validation
            'salary_basic_account_id.required' => __('hr.salary_basic_account_id_required'),
            'salary_basic_account_id.exists' => __('hr.salary_basic_account_id_exists'),
            'opening_balance.numeric' => __('hr.opening_balance_numeric'),

            // Leave balance validation
            'leave_balances.*.leave_type_id.required' => __('hr.leave_balances_leave_type_id_required'),
            'leave_balances.*.leave_type_id.exists' => __('hr.leave_balances_leave_type_id_exists'),
            'leave_balances.*.year.required' => __('hr.leave_balances_year_required'),
            'leave_balances.*.year.integer' => __('hr.leave_balances_year_integer'),
            'leave_balances.*.year.min' => __('hr.leave_balances_year_min'),
            'leave_balances.*.year.max' => __('hr.leave_balances_year_max'),
            'leave_balances.*.opening_balance_days.numeric' => __('hr.leave_balances_opening_balance_days_numeric'),
            'leave_balances.*.opening_balance_days.min' => __('hr.leave_balances_opening_balance_days_min'),
            'leave_balances.*.used_days.numeric' => __('hr.leave_balances_used_days_numeric'),
            'leave_balances.*.used_days.min' => __('hr.leave_balances_used_days_min'),
            'leave_balances.*.pending_days.numeric' => __('hr.leave_balances_pending_days_numeric'),
            'leave_balances.*.pending_days.min' => __('hr.leave_balances_pending_days_min'),
            'leave_balances.*.max_monthly_days.required' => __('hr.leave_balances_max_monthly_days_required'),
            'leave_balances.*.max_monthly_days.numeric' => __('hr.leave_balances_max_monthly_days_numeric'),
            'leave_balances.*.max_monthly_days.min' => __('hr.leave_balances_max_monthly_days_min'),
            'leave_balances.*.notes.string' => __('hr.leave_balances_notes_string'),
            'selected_leave_type_id.exists' => __('hr.selected_leave_type_id_exists'),
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $leaveBalances = $this->input('leave_balances', []);

            foreach ($leaveBalances as $index => $balance) {
                $openingBalance = (float) ($balance['opening_balance_days'] ?? 0);
                $maxMonthly = (float) ($balance['max_monthly_days'] ?? 0);

                if ($maxMonthly > $openingBalance) {
                    $validator->errors()->add(
                        "leave_balances.{$index}.max_monthly_days",
                        __('hr.max_monthly_days_exceeds_opening_balance', [
                            'max_monthly' => number_format($maxMonthly, 1),
                            'opening_balance' => number_format($openingBalance, 1),
                        ])
                    );
                }
            }
        });
    }
}
