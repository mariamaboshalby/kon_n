<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Accounts\Models\AccHead;
use Modules\Accounts\Services\AccountService;
use Modules\HR\Models\City;
use Modules\HR\Models\Country;
use Modules\HR\Models\Department;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeeLeaveBalance;
use Modules\HR\Models\EmployeesJob;
use Modules\HR\Models\Kpi;
use Modules\HR\Models\LeaveType;
use Modules\HR\Models\Shift;
use Modules\HR\Models\State;
use Modules\HR\Models\Town;
use Modules\Settings\Models\Currency;

class EmployeeService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Employee>
     */
    public function listForIndex(?string $search = null, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return Employee::query()
            ->with([
                'department:id,title',
                'job:id,title',
                'shift:id,name,start_time,end_time',
                'projectsAssigned:id,name,project_code',
            ])
            ->select('id', 'name', 'name_ar', 'name_en', 'email', 'phone', 'status', 'department_id', 'job_id', 'shift_id', 'branch_id', 'date_of_hire', 'code')
            ->when($search, function ($q) use ($search) {
                $search = trim($search);
                if ($search !== '') {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('name_ar', 'like', "%{$search}%")
                            ->orWhere('name_en', 'like', "%{$search}%");
                    });
                }
            })
            ->when(! empty($filters['department_id']), fn ($q) => $q->where('department_id', $filters['department_id']))
            ->when(! empty($filters['job_id']), fn ($q) => $q->where('job_id', $filters['job_id']))
            ->when(! empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['date_from']), fn ($q) => $q->whereDate('date_of_hire', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($q) => $q->whereDate('date_of_hire', '<=', $filters['date_to']))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * @return array<string, mixed>
     */
    public function formLookups(?int $excludeEmployeeId = null): array
    {
        $cache = $this->cache();

        return [
            'salary_basic_accounts' => $cache->remember('salary_basic_accounts', 3600, function () {
                return AccHead::where([
                    'acc_type' => 5,
                    'is_basic' => 1,
                ])->select('id', 'aname', 'code')->orderBy('aname')->get();
            }),
            'countries' => $cache->remember('countries_list', 14400, fn () => Country::select('id', 'title')->orderBy('title')->get()),
            'states' => $cache->remember('states_list', 14400, fn () => State::select('id', 'title', 'country_id')->orderBy('title')->get()),
            'cities' => $cache->remember('cities_list', 14400, fn () => City::select('id', 'title', 'state_id')->orderBy('title')->get()),
            'towns' => $cache->remember('towns_list', 14400, fn () => Town::select('id', 'title', 'city_id')->orderBy('title')->get()),
            'departments' => $cache->remember('departments_list', 7200, function () {
                return Department::with('director:id,name')
                    ->select('id', 'title', 'director_id')
                    ->orderBy('title')
                    ->get();
            }),
            'jobs' => $cache->remember('jobs_list', 7200, fn () => EmployeesJob::select('id', 'title')->orderBy('title')->get()),
            'shifts' => $cache->remember('shifts_list', 7200, function () {
                return Shift::select('id', 'name', 'shift_type', 'start_time', 'end_time')->orderBy('name')->get();
            }),
            'kpis' => $cache->remember('kpis_list', 7200, fn () => Kpi::select('id', 'name', 'description')->orderBy('name')->get()),
            'leaveTypes' => $cache->remember('leave_types_list', 7200, fn () => LeaveType::select('id', 'name', 'code')->orderBy('name')->get()),
            'employees' => Employee::query()
                ->when($excludeEmployeeId, fn ($q) => $q->where('id', '!=', $excludeEmployeeId))
                ->select('id', 'name', 'department_id')
                ->orderBy('name')
                ->get(),
            'departmentDirectors' => Employee::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
            'currencies' => Currency::active()->with('latestRate')->orderBy('is_default', 'desc')->orderBy('name')->get(),
            'defaultCurrency' => Currency::default()->first(),
        ];
    }

    /**
     * @return Collection<int, State>
     */
    public function statesByCountry(int $countryId): Collection
    {
        return State::query()
            ->where('country_id', $countryId)
            ->select('id', 'title', 'country_id')
            ->orderBy('title')
            ->get();
    }

    /**
     * @return Collection<int, City>
     */
    public function citiesByState(int $stateId): Collection
    {
        return City::query()
            ->where('state_id', $stateId)
            ->select('id', 'title', 'state_id')
            ->orderBy('title')
            ->get();
    }

    /**
     * @return Collection<int, Town>
     */
    public function townsByCity(int $cityId): Collection
    {
        return Town::query()
            ->where('city_id', $cityId)
            ->select('id', 'title', 'city_id')
            ->orderBy('title')
            ->get();
    }

    public function findForForm(int $id): Employee
    {
        return Employee::with([
            'media',
            'account.haveParent',
            'kpis',
            'leaveBalances.leaveType',
            'department',
            'job',
            'shift',
            'country',
            'city',
            'state',
            'town',
            'lineManager',
        ])->findOrFail($id);
    }

    public function findForShow(int $id): Employee
    {
        return Employee::with([
            'media',
            'job:id,title',
            'department:id,title',
            'shift:id,name,start_time,end_time',
            'lineManager:id,name',
            'country:id,title',
            'city:id,title',
            'state:id,title',
            'town:id,title',
            'kpis',
            'leaveBalances.leaveType',
            'account.haveParent',
            'covenants',
        ])->findOrFail($id);
    }

    /**
     * Form-ready payload for edit Blade / JS.
     *
     * @return array<string, mixed>
     */
    public function formPayload(Employee $employee): array
    {
        $kpiWeights = [];
        foreach ($employee->kpis as $kpi) {
            $kpiWeights[$kpi->id] = $kpi->pivot->weight_percentage;
        }

        $leaveBalances = [];
        foreach ($employee->leaveBalances as $balance) {
            $key = $balance->leave_type_id.'_'.$balance->year;
            $leaveBalances[$key] = [
                'leave_type_id' => $balance->leave_type_id,
                'year' => $balance->year,
                'opening_balance_days' => $balance->opening_balance_days,
                'used_days' => $balance->used_days,
                'pending_days' => $balance->pending_days,
                'max_monthly_days' => $balance->max_monthly_days ?? 0,
                'notes' => $balance->notes,
            ];
        }

        $formatDate = static function ($value): ?string {
            if ($value instanceof \Carbon\Carbon) {
                return $value->format('Y-m-d');
            }

            return $value ? (string) $value : null;
        };

        return [
            'id' => $employee->id,
            'name' => $employee->name,
            'name_ar' => $employee->name_ar,
            'name_en' => $employee->name_en,
            'code' => $employee->code,
            'email' => $employee->email,
            'phone' => $employee->phone,
            'gender' => $employee->gender,
            'date_of_birth' => $formatDate($employee->date_of_birth),
            'nationalId' => $employee->nationalId,
            'marital_status' => $employee->marital_status_english,
            'education' => $employee->education_english,
            'information' => $employee->information,
            'status' => $employee->status_english,
            'country_id' => $employee->country_id,
            'state_id' => $employee->state_id,
            'city_id' => $employee->city_id,
            'town_id' => $employee->town_id,
            'job_id' => $employee->job_id,
            'department_id' => $employee->department_id,
            'line_manager_id' => $employee->line_manager_id,
            'date_of_hire' => $formatDate($employee->date_of_hire),
            'date_of_joining' => $formatDate($employee->date_of_joining),
            'date_of_fire' => $formatDate($employee->date_of_fire),
            'job_level' => $employee->job_level,
            'salary' => $employee->salary,
            'currency_id' => $employee->currency_id,
            'exchange_rate' => $employee->exchange_rate ?? 1.0,
            'currency_salary' => $employee->currency_salary ?? $employee->salary,
            'finger_print_id' => $employee->getRawOriginal('finger_print_id'),
            'finger_print_name' => $employee->getRawOriginal('finger_print_name'),
            'salary_type' => $employee->salary_type,
            'shift_id' => $employee->shift_id,
            'additional_hour_calculation' => $employee->additional_hour_calculation,
            'additional_day_calculation' => $employee->additional_day_calculation,
            'late_hour_calculation' => $employee->late_hour_calculation,
            'late_day_calculation' => $employee->late_day_calculation,
            'flexible_hourly_wage' => $employee->flexible_hourly_wage,
            'allowed_permission_days' => $employee->allowed_permission_days,
            'allowed_late_days' => $employee->allowed_late_days,
            'allowed_absent_days' => $employee->allowed_absent_days,
            'days_off' => $employee->days_off,
            'allowed_errand_days' => $employee->allowed_errand_days,
            'is_errand_allowed' => (bool) $employee->is_errand_allowed,
            'require_location'  => (bool) $employee->require_location,
            'location_type'     => $employee->location_type,
            'salary_basic_account_id' => $employee->account?->parent_id,
            'opening_balance' => $employee->account?->start_balance,
            'current_image_url' => $employee->image_url,
            'kpi_ids' => $employee->kpis->pluck('id')->values()->all(),
            'kpi_weights' => $kpiWeights,
            'leave_balances' => $leaveBalances,
            'national_id_number' => $employee->national_id_number,
            'national_id_start'  => $formatDate($employee->national_id_start),
            'national_id_expiry' => $formatDate($employee->national_id_expiry),
            'residence_number'   => $employee->residence_number,
            'residence_start'    => $formatDate($employee->residence_start),
            'residence_expiry'   => $formatDate($employee->residence_expiry),
            'license_number'     => $employee->license_number,
            'license_start'      => $formatDate($employee->license_start),
            'license_expiry'     => $formatDate($employee->license_expiry),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $image = null): Employee
    {
        $data = $this->sanitizeInputs($data);
        $data = $this->normalizeDefaults($data);
        $this->assertKpiWeights($data);
        $this->assertLeaveBalances($data);

        $employee = null;

        DB::transaction(function () use (&$data, &$employee) {
            [$payload, $meta] = $this->extractPersistPayload($data, isEdit: false);

            $employee = Employee::create($payload);
            $this->syncDepartmentDirector($data);

            $this->syncEmployeeKpis($employee, $meta['kpi_ids'], $meta['kpi_weights']);
            $this->syncLeaveBalances($employee, $meta['leave_balances']);
            $this->syncEmployeeAccount($employee, $meta['salary_basic_account_id'], $meta['opening_balance']);
        });

        if ($image && $employee) {
            $employee->addMedia($image)
                ->usingName($image->getClientOriginalName())
                ->toMediaCollection('employee_images');
        }

        $this->invalidateCache();

        return $employee->fresh([
            'department:id,title',
            'job:id,title',
            'account',
            'kpis',
            'leaveBalances',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Employee $employee, array $data, ?UploadedFile $image = null): Employee
    {
        $data = $this->sanitizeInputs($data);
        $data = $this->normalizeDefaults($data);
        $this->assertKpiWeights($data);
        $this->assertLeaveBalances($data);

        DB::transaction(function () use ($employee, &$data) {
            [$payload, $meta] = $this->extractPersistPayload($data, isEdit: true);

            $employee->update($payload);
            $this->syncDepartmentDirector($data);

            $this->syncEmployeeKpis($employee, $meta['kpi_ids'], $meta['kpi_weights']);
            $this->syncLeaveBalances($employee, $meta['leave_balances']);
            $this->syncEmployeeAccount($employee, $meta['salary_basic_account_id'], $meta['opening_balance']);
        });

        if ($image) {
            $employee->clearMediaCollection('employee_images');
            $employee->addMedia($image)
                ->usingName($image->getClientOriginalName())
                ->toMediaCollection('employee_images');
        }

        $this->invalidateCache();

        return $employee->fresh([
            'department:id,title',
            'job:id,title',
            'account',
            'kpis',
            'leaveBalances',
        ]);
    }

    public function delete(Employee $employee): void
    {
        $employee->delete();
        $this->invalidateCache();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function extractPersistPayload(array $data, bool $isEdit): array
    {
        if (! empty($data['password'])) {
            $data['password'] = Hash::make((string) $data['password']);
        } else {
            unset($data['password']);
        }

        $exchangeRate = isset($data['exchange_rate']) && is_numeric($data['exchange_rate']) && (float)$data['exchange_rate'] > 0
            ? (float)$data['exchange_rate']
            : 1.0;

        $inputSalary = isset($data['currency_salary']) && is_numeric($data['currency_salary'])
            ? (float)$data['currency_salary']
            : (isset($data['salary']) && is_numeric($data['salary']) ? (float)$data['salary'] : null);

        if ($inputSalary !== null) {
            $data['currency_salary'] = $inputSalary;
            $data['exchange_rate'] = $exchangeRate;
            $data['salary'] = round($inputSalary * $exchangeRate, 2);
        }

        $meta = [
            'kpi_ids' => $data['kpi_ids'] ?? [],
            'kpi_weights' => $data['kpi_weights'] ?? [],
            'leave_balances' => $data['leave_balances'] ?? [],
            'salary_basic_account_id' => $data['salary_basic_account_id'] ?? null,
            'opening_balance' => $data['opening_balance'] ?? null,
        ];

        unset(
            $data['kpi_ids'],
            $data['kpi_weights'],
            $data['leave_balances'],
            $data['salary_basic_account_id'],
            $data['opening_balance'],
            $data['selected_kpi_id'],
            $data['selected_leave_type_id'],
            $data['department_director_id'],
            $data['image'],
            $data['isEdit'],
            $data['employeeId'],
            $data['_token'],
            $data['_method']
        );

        if ($isEdit && empty($data['password'] ?? null)) {
            unset($data['password']);
        }

        return [$data, $meta];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncDepartmentDirector(array $data): void
    {
        if (empty($data['department_id'])) {
            return;
        }

        $department = Department::find($data['department_id']);
        if ($department) {
            $department->update([
                'director_id' => $data['department_director_id'] ?: null,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeDefaults(array $data): array
    {
        if (isset($data['finger_print_id']) && $data['finger_print_id'] !== null && $data['finger_print_id'] !== '') {
            $data['finger_print_id'] = (int) $data['finger_print_id'];
        }

        if (($data['salary_basic_account_id'] ?? null) === '') {
            $data['salary_basic_account_id'] = null;
        }

        if (empty($data['status'])) {
            $data['status'] = 'citizen';
        }

        if (empty($data['salary_type'])) {
            $data['salary_type'] = 'ساعات عمل فقط';
        }

        if (($data['flexible_hourly_wage'] ?? null) === null || $data['flexible_hourly_wage'] === '') {
            $data['flexible_hourly_wage'] = 0;
        }

        foreach (['allowed_permission_days', 'allowed_late_days', 'allowed_absent_days', 'days_off', 'allowed_errand_days'] as $field) {
            if (($data[$field] ?? null) === null || $data[$field] === '') {
                $data[$field] = 0;
            }
        }

        if (! isset($data['is_errand_allowed']) || $data['is_errand_allowed'] === '' || $data['is_errand_allowed'] === null) {
            $data['is_errand_allowed'] = false;
        } else {
            $data['is_errand_allowed'] = filter_var($data['is_errand_allowed'], FILTER_VALIDATE_BOOLEAN);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeInputs(array $data): array
    {
        if (isset($data['name'])) {
            $data['name'] = trim(strip_tags((string) $data['name']));
        }

        if (isset($data['name_ar'])) {
            $data['name_ar'] = trim(strip_tags((string) $data['name_ar']));
        }

        if (array_key_exists('email', $data)) {
            $email = trim(strtolower((string) filter_var((string) ($data['email'] ?? ''), FILTER_SANITIZE_EMAIL)));
            $data['email'] = $email === '' ? null : $email;
        }

        if (array_key_exists('phone', $data)) {
            $phone = trim((string) preg_replace('/[^0-9+\-() ]/', '', (string) ($data['phone'] ?? '')));
            $data['phone'] = $phone === '' ? null : $phone;
        }

        if (isset($data['nationalId'])) {
            $data['nationalId'] = trim((string) preg_replace('/[^0-9]/', '', (string) $data['nationalId']));
        }

        if (isset($data['finger_print_name'])) {
            $data['finger_print_name'] = trim(strip_tags((string) $data['finger_print_name']));
        }

        if (isset($data['information'])) {
            $data['information'] = trim(strip_tags((string) $data['information']));
        }

        if (isset($data['salary']) && $data['salary'] !== null && $data['salary'] !== '') {
            $data['salary'] = filter_var($data['salary'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        }

        if (isset($data['opening_balance']) && $data['opening_balance'] !== null && $data['opening_balance'] !== '') {
            $data['opening_balance'] = filter_var($data['opening_balance'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        }

        if (isset($data['leave_balances']) && is_array($data['leave_balances'])) {
            foreach ($data['leave_balances'] as $key => $balance) {
                if (isset($balance['notes'])) {
                    $data['leave_balances'][$key]['notes'] = trim(strip_tags((string) $balance['notes']));
                }
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertKpiWeights(array $data): void
    {
        $kpiIds = $data['kpi_ids'] ?? [];
        $kpiWeights = $data['kpi_weights'] ?? [];

        if (empty($kpiIds) || empty($kpiWeights)) {
            return;
        }

        $totalWeight = 0;
        $selectedKpis = 0;

        foreach ($kpiIds as $kpiId) {
            if (isset($kpiWeights[$kpiId]) && (float) $kpiWeights[$kpiId] > 0) {
                $totalWeight += (float) $kpiWeights[$kpiId];
                $selectedKpis++;
            }
        }

        if ($selectedKpis > 0 && (int) $totalWeight !== 100) {
            throw ValidationException::withMessages([
                'kpi_weights' => [__('hr::hr.kpi_weights_total_not_100', ['total' => $totalWeight])],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertLeaveBalances(array $data): void
    {
        $leaveBalances = $data['leave_balances'] ?? [];
        if (! is_array($leaveBalances) || empty($leaveBalances)) {
            return;
        }

        foreach ($leaveBalances as $index => $balanceData) {
            if (! isset($balanceData['leave_type_id']) || ! isset($balanceData['year'])) {
                continue;
            }

            $openingBalance = (float) ($balanceData['opening_balance_days'] ?? 0);
            $maxMonthly = (float) ($balanceData['max_monthly_days'] ?? 0);

            if ($openingBalance > 0 && $maxMonthly > $openingBalance) {
                throw ValidationException::withMessages([
                    "leave_balances.{$index}.max_monthly_days" => [__('hr::hr.max_monthly_days_exceeds_opening_balance', [
                        'max_monthly' => number_format($maxMonthly, 1),
                        'opening_balance' => number_format($openingBalance, 1),
                    ])],
                ]);
            }
        }
    }

    /**
     * @param  array<int|string>  $kpiIds
     * @param  array<int|string, mixed>  $kpiWeights
     */
    private function syncEmployeeKpis(Employee $employee, array $kpiIds, array $kpiWeights): void
    {
        $kpiData = [];
        foreach ($kpiIds as $kpiId) {
            if (isset($kpiWeights[$kpiId]) && (float) $kpiWeights[$kpiId] > 0) {
                $kpiData[$kpiId] = ['weight_percentage' => $kpiWeights[$kpiId]];
            }
        }
        $employee->kpis()->sync($kpiData);
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $leaveBalances
     */
    private function syncLeaveBalances(Employee $employee, array $leaveBalances): void
    {
        if (! is_array($leaveBalances) || empty($leaveBalances)) {
            return;
        }

        $existingBalances = EmployeeLeaveBalance::where('employee_id', $employee->id)
            ->get()
            ->keyBy(fn ($balance) => $balance->leave_type_id.'_'.$balance->year);

        $toUpdate = [];
        $toCreate = [];

        foreach ($leaveBalances as $balanceData) {
            if (! isset($balanceData['leave_type_id']) || ! isset($balanceData['year'])) {
                continue;
            }

            $key = $balanceData['leave_type_id'].'_'.$balanceData['year'];
            $row = [
                'opening_balance_days' => (float) ($balanceData['opening_balance_days'] ?? 0),
                'used_days' => (float) ($balanceData['used_days'] ?? 0),
                'pending_days' => (float) ($balanceData['pending_days'] ?? 0),
                'max_monthly_days' => (float) ($balanceData['max_monthly_days'] ?? 0),
                'notes' => $balanceData['notes'] ?? null,
            ];

            if ($existingBalances->has($key)) {
                $toUpdate[$existingBalances->get($key)->id] = $row;
            } else {
                $toCreate[] = array_merge([
                    'employee_id' => $employee->id,
                    'leave_type_id' => $balanceData['leave_type_id'],
                    'year' => $balanceData['year'],
                ], $row);
            }
        }

        foreach ($toUpdate as $id => $row) {
            EmployeeLeaveBalance::where('id', $id)->update($row);
        }

        if (! empty($toCreate)) {
            foreach (array_chunk($toCreate, 100) as $chunk) {
                EmployeeLeaveBalance::insert($chunk);
            }
        }

        $currentKeys = array_values(array_filter(array_map(function ($balance) {
            if (! isset($balance['leave_type_id'], $balance['year'])) {
                return null;
            }

            return $balance['leave_type_id'].'_'.$balance['year'];
        }, $leaveBalances)));

        EmployeeLeaveBalance::where('employee_id', $employee->id)
            ->whereNotIn(DB::raw("CONCAT(leave_type_id, '_', year)"), $currentKeys)
            ->delete();
    }

    private function syncEmployeeAccount(Employee $employee, mixed $salaryBasicAccountId, mixed $openingBalance): void
    {
        if ($salaryBasicAccountId === null || $salaryBasicAccountId === '') {
            return;
        }

        $employee->load('account');

        $parentAccount = AccHead::where('id', $salaryBasicAccountId)->first();
        if (! $parentAccount) {
            throw new \RuntimeException('Parent account not found');
        }

        $newCode = $this->generateAccountCode($parentAccount);

        $accountData = [
            'code' => $newCode,
            'aname' => $employee->name,
            'parent_id' => $salaryBasicAccountId,
            'acc_type' => 5,
            'accountable_type' => Employee::class,
            'accountable_id' => $employee->id,
        ];

        if (! $employee->account) {
            $employee->account()->create($accountData);
            $employee->load('account');
        } else {
            unset($accountData['accountable_type'], $accountData['accountable_id']);
            $employee->account->update($accountData);
        }

        $this->updateEmployeeAccountBalances($employee, $openingBalance);
        $this->createEmployeeSubAccounts($employee);
    }

    private function updateEmployeeAccountBalances(Employee $employee, mixed $openingBalance): void
    {
        if (! $employee->account) {
            return;
        }

        $accountService = app(AccountService::class);
        $accountService->setStartBalances([$employee->account->id => $openingBalance]);
        $accountService->recalculateOpeningCapitalAndSyncJournal();
    }

    private function createEmployeeSubAccounts(Employee $employee): void
    {
        $this->createOrUpdateSubAccount($employee, '110601', 'سلف '.$employee->name);
        $this->createOrUpdateSubAccount($employee, '4202', 'جزاءات وخصومات '.$employee->name);
        $this->createOrUpdateSubAccount($employee, '5303', 'مكافآت وحوافز '.$employee->name);
        $this->createOrUpdateSubAccount($employee, '5304', 'إضافى حضور '.$employee->name);
        $this->createOrUpdateSubAccount($employee, '4203', 'خصم حضور '.$employee->name);
        $this->createOrUpdateSubAccount($employee, '110602', 'جزاءات وخصومات مستحقه '.$employee->name);
        $this->createOrUpdateSubAccount($employee, '2109', 'مكافآت وحوافز مستحقه '.$employee->name);
    }

    private function createOrUpdateSubAccount(Employee $employee, string $parentCode, string $accountName): void
    {
        $parentAccount = AccHead::where('code', $parentCode)->first();
        if (! $parentAccount) {
            Log::warning("Parent account with code {$parentCode} not found for employee {$employee->id}");

            return;
        }

        $existingAccount = AccHead::where('accountable_type', Employee::class)
            ->where('accountable_id', $employee->id)
            ->where('aname', $accountName)
            ->where('parent_id', $parentAccount->id)
            ->first();

        if ($existingAccount) {
            $existingAccount->update([
                'aname' => $accountName,
                'mdtime' => now(),
            ]);

            return;
        }

        $newCode = $this->generateAccountCode($parentAccount);

        AccHead::create([
            'code' => (string) $newCode,
            'aname' => $accountName,
            'parent_id' => $parentAccount->id,
            'acc_type' => 5,
            'accountable_type' => Employee::class,
            'accountable_id' => $employee->id,
            'is_basic' => 0,
            'deletable' => 0,
            'editable' => 1,
            'is_stock' => 0,
            'is_fund' => 0,
            'start_balance' => 0,
            'credit' => 0,
            'debit' => 0,
            'balance' => 0,
            'crtime' => now(),
            'mdtime' => now(),
        ]);
    }

    private function generateAccountCode(AccHead $parentAccount): string
    {
        $children = $parentAccount->haveChildrens()->get();

        $maxChildCode = null;
        foreach ($children as $child) {
            if (is_numeric($child->code)) {
                $childCodeValue = (int) $child->code;
                if ($maxChildCode === null || $childCodeValue > $maxChildCode) {
                    $maxChildCode = $childCodeValue;
                }
            }
        }

        if ($maxChildCode !== null) {
            return (string) ($maxChildCode + 1);
        }

        $parentCodeValue = (int) $parentAccount->code;
        $newCodeValue = $parentCodeValue + 1;

        while (AccHead::where('code', (string) $newCodeValue)->exists()) {
            $newCodeValue++;
        }

        return (string) $newCodeValue;
    }

    private function invalidateCache(): void
    {
        $store = Cache::getStore();
        if (method_exists($store, 'tags')) {
            try {
                Cache::tags(['employee_form_data'])->flush();

                return;
            } catch (\BadMethodCallException) {
                // fall through
            }
        }

        foreach ([
            'salary_basic_accounts',
            'countries_list',
            'cities_list',
            'states_list',
            'towns_list',
            'departments_list',
            'jobs_list',
            'shifts_list',
            'kpis_list',
            'leave_types_list',
        ] as $key) {
            Cache::forget($key);
        }
    }

    private function cache()
    {
        $store = Cache::getStore();
        if (method_exists($store, 'tags')) {
            try {
                return Cache::tags(['employee_form_data']);
            } catch (\BadMethodCallException) {
                return Cache::store();
            }
        }

        return Cache::store();
    }
}
