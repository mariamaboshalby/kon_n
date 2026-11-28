<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeeLeaveBalance;
use Modules\HR\Models\HRSetting;
use Modules\HR\Models\LeaveRequest;
use Modules\HR\Models\LeaveType;
use Modules\HR\Models\Department;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LeaveBalanceService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * حجز أيام معلقة في رصيد الموظف
     */
    public function reservePending(int $employeeId, int $leaveTypeId, int $year, float $days): bool
    {
        try {
            DB::beginTransaction();

            $balance = $this->getOrCreateBalance($employeeId, $leaveTypeId, $year);

            if (! $balance->hasSufficientBalance($days)) {
                DB::rollBack();

                return false;
            }

            $balance->reservePending($days);
            $balance->save();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * استهلاك أيام معتمدة من رصيد الموظف
     */
    public function consumeApproved(int $employeeId, int $leaveTypeId, int $year, float $days, ?int $month = null): void
    {
        DB::transaction(function () use ($employeeId, $leaveTypeId, $year, $days, $month) {
            $balance = $this->getOrCreateBalance($employeeId, $leaveTypeId, $year);

            // التحقق من أن pending_days كافية
            if ($balance->pending_days < $days) {
                throw new \RuntimeException(
                    sprintf(
                        'Insufficient pending days for employee %d. Required: %s, Available: %s',
                        $employeeId,
                        $days,
                        $balance->pending_days
                    )
                );
            }

            // التحقق من الرصيد المتبقي (بعد إزالة pending_days)
            $availableAfterRelease = $balance->remaining_days + $balance->pending_days;
            if ($availableAfterRelease < $days) {
                throw new \RuntimeException(
                    sprintf(
                        'Insufficient balance to consume for employee %d. Required: %s, Available: %s',
                        $employeeId,
                        $days,
                        $availableAfterRelease
                    )
                );
            }

            $balance->consumeApproved($days);

            // تحديث monthly_used_days (استخدام الشهر المحدد أو الشهر الحالي)
            $targetMonth = $month ?? Carbon::now()->month;
            $balance->addMonthlyUsedDays($targetMonth, $days);
            $balance->save();
        });
    }

    /**
     * إطلاق أيام معلقة من رصيد الموظف
     */
    public function releasePending(int $employeeId, int $leaveTypeId, int $year, float $days): void
    {
        DB::transaction(function () use ($employeeId, $leaveTypeId, $year, $days) {
            $balance = $this->getOrCreateBalance($employeeId, $leaveTypeId, $year);
            $balance->releasePending($days);
            $balance->save();
        });
    }

    /**
     * إعادة حساب الرصيد المتبقي
     */
    public function recalculateRemaining(int $employeeId, int $leaveTypeId, int $year): float
    {
        $balance = $this->getOrCreateBalance($employeeId, $leaveTypeId, $year);

        return $balance->remaining_days;
    }

    /**
     * الحصول على رصيد موجود أو إنشاء رصيد جديد
     */
    public function getOrCreateBalance(int $employeeId, int $leaveTypeId, int $year): EmployeeLeaveBalance
    {
        return EmployeeLeaveBalance::firstOrCreate(
            [
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'year' => $year,
            ],
            [
                'opening_balance_days' => 0,
                'used_days' => 0,
                'pending_days' => 0,
                'monthly_used_days' => [],
            ]
        );
    }

    /**
     * التحقق من كفاية الرصيد لطلب إجازة
     */
    public function hasSufficientBalance(int $employeeId, int $leaveTypeId, int $year, float $days): bool
    {
        $balance = $this->getOrCreateBalance($employeeId, $leaveTypeId, $year);

        return $balance->hasSufficientBalance($days);
    }

    /**
     * الحصول على رصيد الموظف لجميع أنواع الإجازات في سنة معينة
     */
    public function getEmployeeBalances(int $employeeId, int $year): \Illuminate\Database\Eloquent\Collection
    {
        return EmployeeLeaveBalance::with(['leaveType'])
            ->where('employee_id', $employeeId)
            ->where('year', $year)
            ->get();
    }

    /**
     * التحقق من تداخل الطلبات المعتمدة
     */
    public function hasOverlappingApprovedRequests(int $employeeId, string $startDate, string $endDate, ?int $excludeRequestId = null): bool
    {
        $query = LeaveRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->overlapping($startDate, $endDate);

        if ($excludeRequestId) {
            $query->where('id', '!=', $excludeRequestId);
        }

        return $query->exists();
    }

    /**
     * حساب أيام العمل الفعلية (استثناء العطلات الرسمية)
     */
    public function calculateWorkingDays(string $startDate, string $endDate, bool $excludeHolidays = true): float
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $workingDays = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            // استثناء أيام العطل الأسبوعية (الجمعة والسبت)
            if (! $current->isFriday() && ! $current->isSaturday()) {
                // TODO: إضافة استثناء العطلات الرسمية من جدول official_holidays
                $workingDays++;
            }
            $current->addDay();
        }

        return $workingDays;
    }

    /**
     * حساب عدد الموظفين في الإجازة في فترة معينة
     */
    public function getEmployeesOnLeaveCount(string $startDate, string $endDate, ?int $departmentId = null, ?int $excludeRequestId = null): int
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        Log::info('--- getEmployeesOnLeaveCount ---');
        Log::info('Period: ' . $start->format('Y-m-d') . ' to ' . $end->format('Y-m-d'));
        Log::info('Department ID: ' . ($departmentId ?? 'null'));
        Log::info('Exclude Request ID: ' . ($excludeRequestId ?? 'null'));

        // الحصول على جميع الموظفين في الإجازة في أي يوم خلال الفترة
        $query = LeaveRequest::where('status', 'approved')
            ->when($excludeRequestId, function ($q) use ($excludeRequestId) {
                $q->where('id', '!=', $excludeRequestId);
            })
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                    ->orWhereBetween('end_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                    ->orWhere(function ($subQ) use ($start, $end) {
                        $subQ->where('start_date', '<=', $start->format('Y-m-d'))
                            ->where('end_date', '>=', $end->format('Y-m-d'));
                    });
            })
            ->whereHas('employee', function ($q) use ($departmentId) {
                if ($departmentId !== null) {
                    $q->where('department_id', $departmentId);
                }
                // فقط الموظفين النشطين
                $q->where('status', 'مفعل');
            });

        // الحصول على قائمة الموظفين في الإجازة للتحقق
        $employeeIds = $query->distinct()->pluck('employee_id')->toArray();
        Log::info('Employee IDs on leave: ' . json_encode($employeeIds));
        Log::info('Count: ' . count($employeeIds));

        // الحصول على عدد الموظفين الفريدين (لأن موظف واحد قد يكون له أكثر من إجازة)
        $count = $query->distinct()->count('employee_id');
        Log::info('Final count: ' . $count);
        Log::info('--- getEmployeesOnLeaveCount END ---');

        return $count;
    }

    /**
     * التحقق من النسبة المئوية للإجازات
     */
    public function checkLeavePercentageLimit(int $employeeId, string $startDate, string $endDate, ?int $departmentId = null, ?int $excludeRequestId = null): bool
    {
        Log::info('=== checkLeavePercentageLimit START ===');
        Log::info('Employee ID: ' . $employeeId);
        Log::info('Start Date: ' . $startDate);
        Log::info('End Date: ' . $endDate);
        Log::info('Department ID: ' . ($departmentId ?? 'null'));
        Log::info('Exclude Request ID: ' . ($excludeRequestId ?? 'null'));

        // الحصول على عدد الموظفين في الإجازة
        $employeesOnLeave = $this->getEmployeesOnLeaveCount($startDate, $endDate, $departmentId, $excludeRequestId);
        Log::info('Employees on Leave Count: ' . $employeesOnLeave);

        // الحصول على إجمالي عدد الموظفين
        $totalEmployeesQuery = Employee::where('status', 'مفعل');
        if ($departmentId !== null) {
            $totalEmployeesQuery->where('department_id', $departmentId);
        }
        $totalEmployees = $totalEmployeesQuery->count();
        Log::info('Total Employees: ' . $totalEmployees);

        if ($totalEmployees === 0) {
            Log::info('No employees found, returning true');
            return true; // لا يوجد موظفين، لا حاجة للتحقق
        }

        // الحصول على النسبة المئوية القصوى
        // الأولوية: 1. departments.max_leave_percentage, 2. hr_settings.company_max_leave_percentage
        $maxPercentage = null;
        $percentageSource = 'none';

        if ($departmentId !== null) {
            $department = Department::find($departmentId);
            if ($department && ! is_null($department->max_leave_percentage)) {
                $maxPercentage = (float) $department->max_leave_percentage;
                $percentageSource = 'department';
                Log::info('Department found: ' . $department->name . ', Max Percentage: ' . $maxPercentage);
            } else {
                Log::info('Department not found or no percentage set');
            }
        }

        // إذا لم توجد نسبة للقسم، استخدم إعدادات الشركة
        if ($maxPercentage === null) {
            $maxPercentage = HRSetting::getCompanyMaxLeavePercentage();
            if ($maxPercentage !== null) {
                $percentageSource = 'company';
                Log::info('Using Company Max Percentage: ' . $maxPercentage);
            } else {
                Log::info('Company Max Percentage is null');
            }
        }

        // إذا لم توجد أي نسبة محددة، رفض الموافقة مع رسالة خطأ
        if ($maxPercentage === null) {
            Log::warning('No percentage limit found, returning false');
            return false;
        }

        // حساب النسبة المئوية الحالية
        $currentPercentage = ($employeesOnLeave / $totalEmployees) * 100;
        Log::info('Current Percentage: ' . number_format($currentPercentage, 2) . '%');

        // التحقق من أن النسبة الحالية + 1 (الموظف الجديد) لا تتجاوز الحد
        // نحسب النسبة بعد إضافة الموظف الحالي
        $newEmployeesOnLeave = $employeesOnLeave;

        // التحقق من أن الموظف الحالي ليس في الإجازة بالفعل في هذه الفترة
        $employeeAlreadyOnLeave = LeaveRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->when($excludeRequestId, function ($q) use ($excludeRequestId) {
                $q->where('id', '!=', $excludeRequestId);
            })
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($subQ) use ($startDate, $endDate) {
                        $subQ->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->exists();

        Log::info('Employee Already on Leave: ' . ($employeeAlreadyOnLeave ? 'YES' : 'NO'));

        if (! $employeeAlreadyOnLeave) {
            $newEmployeesOnLeave++;
            Log::info('Adding employee to count. New count: ' . $newEmployeesOnLeave);
        } else {
            Log::info('Employee already counted, keeping count: ' . $newEmployeesOnLeave);
        }

        $newPercentage = ($newEmployeesOnLeave / $totalEmployees) * 100;
        Log::info('New Percentage (after adding employee): ' . number_format($newPercentage, 2) . '%');
        Log::info('Max Percentage Allowed: ' . number_format($maxPercentage, 2) . '% (from: ' . $percentageSource . ')');
        Log::info('Result: ' . ($newPercentage <= $maxPercentage ? 'PASS' : 'FAIL'));
        Log::info('=== checkLeavePercentageLimit END ===');

        return $newPercentage <= $maxPercentage;
    }

    /**
     * التحقق من الحد الشهري
     */
    public function checkMonthlyLimit(int $employeeId, int $leaveTypeId, int $year, int $month, float $days): bool
    {
        $balance = $this->getOrCreateBalance($employeeId, $leaveTypeId, $year);

        return ! $balance->hasExceededMonthlyLimit($month, $days);
    }

    /**
     * @param  array{search?: string|null, employee_id?: string|int|null, leave_type_id?: string|int|null, year?: string|int|null}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function listBalances(array $filters = []): Collection
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $employeeId = $filters['employee_id'] ?? null;
        $leaveTypeId = $filters['leave_type_id'] ?? null;
        $year = $filters['year'] ?? null;

        $balances = EmployeeLeaveBalance::query()
            ->with(['employee:id,name', 'leaveType:id,name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%');
                });
            })
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->when($leaveTypeId, fn ($q) => $q->where('leave_type_id', $leaveTypeId))
            ->when($year, fn ($q) => $q->where('year', $year))
            ->orderByDesc('year')
            ->orderBy('employee_id')
            ->get();

        return $balances->map(fn (EmployeeLeaveBalance $balance) => $this->serializeBalance($balance))->values();
    }

    /**
     * @return EloquentCollection<int, Employee>
     */
    public function employeesForSelect(): EloquentCollection
    {
        return Employee::query()->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @return EloquentCollection<int, LeaveType>
     */
    public function leaveTypesForSelect(): EloquentCollection
    {
        return LeaveType::query()->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createBalance(array $data): EmployeeLeaveBalance
    {
        $this->assertBalanceRules($data);

        return EmployeeLeaveBalance::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateBalance(EmployeeLeaveBalance $balance, array $data): EmployeeLeaveBalance
    {
        $this->assertBalanceRules($data, $balance->id);
        $balance->update($data);

        return $balance->fresh(['employee', 'leaveType']) ?? $balance;
    }

    public function deleteBalance(EmployeeLeaveBalance $balance): void
    {
        $balance->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertBalanceRules(array $data, ?int $ignoreId = null): void
    {
        $maxMonthly = (float) ($data['max_monthly_days'] ?? 0);
        $opening = (float) ($data['opening_balance_days'] ?? 0);

        if ($maxMonthly > $opening) {
            throw ValidationException::withMessages([
                'max_monthly_days' => [__('hr::hr.max_monthly_days_exceeds_opening_balance', [
                    'max_monthly' => number_format($maxMonthly, 1),
                    'opening_balance' => number_format($opening, 1),
                ])],
            ]);
        }

        $exists = EmployeeLeaveBalance::query()
            ->where('employee_id', $data['employee_id'])
            ->where('leave_type_id', $data['leave_type_id'])
            ->where('year', $data['year'])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'general' => [__('hr::hr.duplicate_leave_balance')],
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeBalance(EmployeeLeaveBalance $balance): array
    {
        return [
            'id' => $balance->id,
            'employee_id' => $balance->employee_id,
            'leave_type_id' => $balance->leave_type_id,
            'employee_name' => $balance->employee?->name ?? '',
            'leave_type_name' => $balance->leaveType?->name ?? '',
            'year' => $balance->year,
            'opening_balance_days' => (float) $balance->opening_balance_days,
            'used_days' => (float) $balance->used_days,
            'pending_days' => (float) $balance->pending_days,
            'max_monthly_days' => $balance->max_monthly_days !== null ? (float) $balance->max_monthly_days : null,
            'remaining_days' => (float) $balance->remaining_days,
            'notes' => $balance->notes,
        ];
    }
}
