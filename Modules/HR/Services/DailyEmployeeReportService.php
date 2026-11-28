<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\HR\Models\Attendance;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Errand;
use Modules\HR\Models\LeaveRequest;
use Modules\HR\Models\WorkPermission;

class DailyEmployeeReportService
{
    /**
     * Generate comprehensive daily employee report for a date range
     *
     * @param string      $dateFrom     Start date in Y-m-d format
     * @param string      $dateTo       End date in Y-m-d format
     * @param int|null    $departmentId Optional department filter
     * @param int|null    $employeeId   Optional employee filter
     * @param string      $searchName   Optional employee name search
     * @return array
     */
    public function generateDailyReport(
        string $dateFrom,
        string $dateTo,
        ?int $departmentId = null,
        ?int $employeeId = null,
        string $searchName = '',
    ): array {
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to   = Carbon::parse($dateTo)->startOfDay();

        // Clamp range to 31 days to avoid memory issues
        if ($from->diffInDays($to) > 30) {
            $to = $from->copy()->addDays(30);
        }

        $employeesQuery = Employee::where('status', '!=', 'خارج الشركة')
            ->with(['department', 'shift']);

        if ($departmentId) {
            $employeesQuery->where('department_id', $departmentId);
        }

        if ($employeeId) {
            $employeesQuery->where('id', $employeeId);
        }

        if ($searchName) {
            $employeesQuery->where('name', 'like', '%' . $searchName . '%');
        }

        $employees = $employeesQuery->get();

        // Build one report per day in the range
        $days = [];
        $current = $from->copy();

        while ($current->lte($to)) {
            $days[] = $this->buildDayReport($employees, $current->copy(), $departmentId);
            $current->addDay();
        }

        // Aggregate totals across all days
        $totals = [
            'total_employees'              => $employees->count(),
            'present_count'                => array_sum(array_column($days, 'present_count')),
            'absent_count'                 => array_sum(array_column($days, 'absent_count')),
            'on_leave_count'               => array_sum(array_column($days, 'on_leave_count')),
            'on_errand_count'              => array_sum(array_column($days, 'on_errand_count')),
            'with_permission_count'        => array_sum(array_column($days, 'with_permission_count')),
            'late_count'                   => array_sum(array_column($days, 'late_count')),
            'early_departure_count'        => array_sum(array_column($days, 'early_departure_count')),
            'pending_leave_requests_count' => array_sum(array_column($days, 'pending_leave_requests_count')),
        ];

        // Calculate total work hours from attendance records
        $totalWorkHours = 0;
        $totalOvertimeMinutes = 0;

        // Fetch one extra day for overnight shifts so post-midnight exits can be
        // re-attributed to their work day, then group every employee's punches by
        // work day (same rule as the salary engine) before pairing check-in/out.
        $employeeShifts = $employees->pluck('shift', 'id');
        $needsExtraDay = $employees->contains(fn ($e) => $e->shift && $e->shift->isOvernightShift());
        $hoursFetchEnd = $needsExtraDay ? $to->copy()->addDay() : $to->copy();

        $allPunches = Attendance::whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('date', [$from->toDateString(), $hoursFetchEnd->toDateString()])
            ->where('status', 'approved')
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        $approvedByWorkday = collect();
        foreach ($allPunches->groupBy('employee_id') as $empId => $records) {
            foreach (OvernightAttendanceService::groupByWorkday($records, $employeeShifts->get($empId)) as $dateStr => $group) {
                // إعادة النسب بيُنقل الانصراف لمجموعة اليوم السابق، فالتاريخ
                // المخزَّن على السجل نفسه مش بالضرورة يوم الشغل — بنثبّت
                // يوم الشغل في مفتاح المجموعة ($dateStr) ونستخدمه في الحساب.
                $approvedByWorkday->put($empId . '|' . $dateStr, [
                    'workday' => $dateStr,
                    'records' => collect($group),
                ]);
            }
        }

        // Aggregate per employee per work day: first check-in + last check-out,
        // mirroring the salary engine's pairing instead of one row per punch.
        foreach ($approvedByWorkday as $entry) {
            $group = $entry['records'];

            // تجاهل يوم الشغل اللي برّه النطاق (يوم إضافي اجتبناه بس عشان
            // نلمّ انصراف ما بعد منتصف الليل الخاص بآخر يوم في النطاق).
            $workday = $entry['workday'];
            if ($workday < $from->toDateString() || $workday > $to->toDateString()) {
                continue;
            }

            $firstCheckIn = $group->where('type', 'check_in')->sortBy('time')->first();
            $lastCheckOut = $group->where('type', 'check_out')->sortBy('time')->last();

            if (! $firstCheckIn || ! $lastCheckOut) {
                continue;
            }

            $employee = $employees->find($group->first()->employee_id);
            $shift = $employee?->shift;

            $checkInTime = Carbon::parse($workday . ' ' . $firstCheckIn->time);
            $checkOutTime = Carbon::parse($workday . ' ' . $lastCheckOut->time);

            // Overnight shift: an exit earlier than the entry belongs to the
            // next calendar day (post-midnight end of this work day's shift).
            if ($shift && $shift->isOvernightShift() && $lastCheckOut->time < $firstCheckIn->time) {
                $checkOutTime->addDay();
            }

            $hours = (int) round(abs($checkInTime->diffInMinutes($checkOutTime))) / 60;

            if ($shift && $shift->start_time && $shift->end_time) {
                $refDate = Carbon::parse('2000-01-01');
                $expectedHours = $refDate->copy()->setTimeFromTimeString($shift->start_time)
                    ->diffInMinutes(
                        $refDate->copy()->setTimeFromTimeString($shift->end_time)
                            ->addDay($shift->isOvernightShift() ? 1 : 0)
                    ) / 60;
            } else {
                $expectedHours = $shift?->hours_per_day ?? 8;
            }

            if ($hours > $expectedHours) {
                $totalOvertimeMinutes += ($hours - $expectedHours) * 60;
                $totalWorkHours += $expectedHours;
            } else {
                $totalWorkHours += $hours;
            }
        }
        
        $totals['total_work_hours'] = round($totalWorkHours, 2);
        $totals['total_overtime_hours'] = round($totalOvertimeMinutes / 60, 2);

        $totalExpected = $totals['total_employees'] * count($days);
        $totals['attendance_percentage'] = $totalExpected > 0
            ? round(($totals['present_count'] / $totalExpected) * 100, 2)
            : 0;

        return [
            'date_from'       => $from->format('Y-m-d'),
            'date_to'         => $to->format('Y-m-d'),
            'date_from_formatted' => $from->translatedFormat('d F Y'),
            'date_to_formatted'   => $to->translatedFormat('d F Y'),
            'is_range'        => $from->ne($to),
            'days'            => $days,
            'totals'          => $totals,
            // Keep flat arrays for the first/only day (backward compat with single-day view)
            'date'            => $from->format('Y-m-d'),
            'date_formatted'  => count($days) === 1
                ? $from->translatedFormat('l، d F Y')
                : $from->translatedFormat('d F Y') . ' — ' . $to->translatedFormat('d F Y'),
            'summary'         => count($days) === 1 ? $days[0]['summary'] : $totals,
            'present'         => count($days) === 1 ? $days[0]['present']         : $this->mergeRows($days, 'present'),
            'absent'          => count($days) === 1 ? $days[0]['absent']          : $this->mergeRows($days, 'absent'),
            'on_leave'        => count($days) === 1 ? $days[0]['on_leave']        : $this->mergeRows($days, 'on_leave'),
            'on_errand'       => count($days) === 1 ? $days[0]['on_errand']       : $this->mergeRows($days, 'on_errand'),
            'with_permission' => count($days) === 1 ? $days[0]['with_permission'] : $this->mergeRows($days, 'with_permission'),
            'late'            => count($days) === 1 ? $days[0]['late']            : $this->mergeRows($days, 'late'),
            'early_departure' => count($days) === 1 ? $days[0]['early_departure'] : $this->mergeRows($days, 'early_departure'),
            'pending_leave_requests' => count($days) === 1
                ? $days[0]['pending_leave_requests']
                : collect($this->mergeRows($days, 'pending_leave_requests'))
                    ->unique('id')
                    ->values()
                    ->toArray(),
        ];
    }

    /**
     * Build the report data for a single day
     */
    private function buildDayReport(Collection $employees, Carbon $targetDate, ?int $departmentId): array
    {
        $present        = [];
        $absent         = [];
        $onLeave        = [];
        $onErrand       = [];
        $withPermission = [];
        $late           = [];
        $earlyDeparture = [];

        foreach ($employees as $employee) {
            $employeeData = [
                'id'              => $employee->id,
                'name'            => $employee->name,
                'department'      => $employee->department?->title ?? '-',
                'project_code'    => '-',
                'shift'           => $employee->shift?->name ?? '-',
                'finger_print_id' => $employee->finger_print_id,
                'date'            => $targetDate->format('Y-m-d'),
            ];

            $approvedLeave = $this->getApprovedLeave($employee, $targetDate);
            if ($approvedLeave) {
                $employeeData['leave_type']     = $approvedLeave->leaveType->name ?? '';
                $employeeData['leave_start']    = $approvedLeave->start_date->format('Y-m-d');
                $employeeData['leave_end']      = $approvedLeave->end_date->format('Y-m-d');
                $employeeData['leave_duration'] = $approvedLeave->duration_days;
                $employeeData['is_paid']        = $approvedLeave->leaveType->is_paid ?? false;
                $onLeave[] = $employeeData;
                continue;
            }

            $approvedErrand = $this->getApprovedErrand($employee, $targetDate);
            if ($approvedErrand) {
                $employeeData['errand_title']    = $approvedErrand->title;
                $employeeData['errand_start']    = $approvedErrand->start_date->format('Y-m-d');
                $employeeData['errand_end']      = $approvedErrand->end_date->format('Y-m-d');
                $employeeData['errand_location'] = $approvedErrand->location ?? '';
                $onErrand[] = $employeeData;
                continue;
            }

            $approvedPermission = $this->getApprovedWorkPermission($employee, $targetDate);
            if ($approvedPermission) {
                $employeeData['permission_reason'] = $approvedPermission->reason ?? '';
                $employeeData['permission_date']   = $approvedPermission->date->format('Y-m-d');
                $withPermission[] = $employeeData;
                continue;
            }

            $attendanceRecords = $this->getAttendanceRecords($employee, $targetDate);

            if ($attendanceRecords->isNotEmpty()) {
                $checkIns  = $attendanceRecords->where('type', 'check_in');
                $checkOuts = $attendanceRecords->where('type', 'check_out');

                $firstCheckIn  = $checkIns->sortBy('time')->first();
                $lastCheckOut  = $checkOuts->sortByDesc('time')->first();

                $employeeData['check_in_time']    = $firstCheckIn?->time ?? null;
                $employeeData['check_out_time']   = $lastCheckOut?->time ?? null;
                $employeeData['attendance_count'] = $attendanceRecords->count();
                // Project code recorded on the check-in fingerprint
                $employeeData['project_code']     = $firstCheckIn?->project_code ?: '-';

                if ($this->isLate($employee, $firstCheckIn, $targetDate)) {
                    $employeeData['late_minutes'] = $this->calculateLateMinutes($employee, $firstCheckIn, $targetDate);
                    $late[] = $employeeData;
                }

                if ($this->isEarlyDeparture($employee, $lastCheckOut, $targetDate)) {
                    $employeeData['early_minutes'] = $this->calculateEarlyMinutes($employee, $lastCheckOut, $targetDate);
                    $earlyDeparture[] = $employeeData;
                }

                $present[] = $employeeData;
            } else {
                $absent[] = $employeeData;
            }
        }

        $pendingLeaveRequests = $this->getPendingLeaveRequests($targetDate, $departmentId);

        $summary = [
            'total_employees'              => $employees->count(),
            'present_count'                => \count($present),
            'absent_count'                 => \count($absent),
            'on_leave_count'               => \count($onLeave),
            'on_errand_count'              => \count($onErrand),
            'with_permission_count'        => \count($withPermission),
            'late_count'                   => \count($late),
            'early_departure_count'        => \count($earlyDeparture),
            'pending_leave_requests_count' => $pendingLeaveRequests->count(),
            'attendance_percentage'        => $employees->count() > 0
                ? round((\count($present) / $employees->count()) * 100, 2)
                : 0,
        ];

        return [
            'date'                   => $targetDate->format('Y-m-d'),
            'date_formatted'         => $targetDate->translatedFormat('l، d F Y'),
            'summary'                => $summary,
            'present'                => $present,
            'absent'                 => $absent,
            'on_leave'               => $onLeave,
            'on_errand'              => $onErrand,
            'with_permission'        => $withPermission,
            'late'                   => $late,
            'early_departure'        => $earlyDeparture,
            'pending_leave_requests' => $pendingLeaveRequests->map(fn ($r) => [
                'id'            => $r->id,
                'employee_name' => $r->employee->name ?? '',
                'employee_id'   => $r->employee_id,
                'department'    => $r->employee->department?->title ?? '',
                'leave_type'    => $r->leaveType->name ?? '',
                'start_date'    => $r->start_date->format('Y-m-d'),
                'end_date'      => $r->end_date->format('Y-m-d'),
                'duration_days' => $r->duration_days,
                'reason'        => $r->reason ?? '',
                'status'        => $r->status,
            ])->toArray(),
            // Expose flat counts for array_column aggregation
            'present_count'                => \count($present),
            'absent_count'                 => \count($absent),
            'on_leave_count'               => \count($onLeave),
            'on_errand_count'              => \count($onErrand),
            'with_permission_count'        => \count($withPermission),
            'late_count'                   => \count($late),
            'early_departure_count'        => \count($earlyDeparture),
            'pending_leave_requests_count' => $pendingLeaveRequests->count(),
        ];
    }

    /**
     * Merge a specific key from all day reports into one flat array
     *
     * @param array<int, array<string, mixed>> $days
     */
    private function mergeRows(array $days, string $key): array
    {
        return array_merge(...array_column($days, $key));
    }
    
    /**
     * Get approved leave for employee on specific date
     */
    private function getApprovedLeave(Employee $employee, Carbon $date): ?LeaveRequest
    {
        return LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date->format('Y-m-d'))
            ->whereDate('end_date', '>=', $date->format('Y-m-d'))
            ->with('leaveType')
            ->first();
    }
    
    /**
     * Get approved errand for employee on specific date
     */
    private function getApprovedErrand(Employee $employee, Carbon $date): ?Errand
    {
        return Errand::where('employee_id', $employee->id)
            ->whereNotNull('approved_at')
            ->whereDate('start_date', '<=', $date->format('Y-m-d'))
            ->whereDate('end_date', '>=', $date->format('Y-m-d'))
            ->first();
    }
    
    /**
     * Get approved work permission for employee on specific date
     */
    private function getApprovedWorkPermission(Employee $employee, Carbon $date): ?WorkPermission
    {
        return WorkPermission::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('date', $date->format('Y-m-d'))
            ->first();
    }
    
    /**
     * Get attendance records for employee on specific work day.
     *
     * Overnight shifts: an exit punched after midnight (stored on the next
     * calendar date) is re-attributed to this work day, and this day's own
     * post-midnight exit is pulled from the next day's raw records.
     */
    private function getAttendanceRecords(Employee $employee, Carbon $date): Collection
    {
        $overnight = $employee->shift && $employee->shift->isOvernightShift();

        // For overnight shifts fetch [D-1, D+1]:
        //  - D+1 supplies this work day's own post-midnight exit (moves in),
        //  - D-1 is the anchor that lets an exit stored on D move OUT to D-1
        //    when it is really the previous night's shift end (no phantom row).
        $fetchFrom = $overnight ? $date->copy()->subDay() : $date->copy();
        $fetchTo   = $overnight ? $date->copy()->addDay() : $date->copy();

        $records = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [
                $fetchFrom->format('Y-m-d'),
                $fetchTo->format('Y-m-d'),
            ])
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        if (! $overnight) {
            return $records;
        }

        $byWorkday = OvernightAttendanceService::groupByWorkday($records, $employee->shift);

        return $byWorkday[$date->format('Y-m-d')] ?? collect();
    }
    
    /**
     * Check if employee is late
     */
    private function isLate(Employee $employee, ?Attendance $checkIn, Carbon $date): bool
    {
        if (!$checkIn || !$employee->shift || !$employee->shift->start_time) {
            return false;
        }
        
        $checkInTime = Carbon::parse($date->format('Y-m-d') . ' ' . $checkIn->time);
        $shiftStart = Carbon::parse($date->format('Y-m-d') . ' ' . $employee->shift->start_time);
        
        // Add allowed late minutes if configured
        $allowedLateMinutes = $employee->shift->allowed_late_minutes ?? 0;
        $shiftStart->addMinutes($allowedLateMinutes);
        
        return $checkInTime->gt($shiftStart);
    }
    
    /**
     * Calculate late minutes
     */
    private function calculateLateMinutes(Employee $employee, ?Attendance $checkIn, Carbon $date): int
    {
        if (!$checkIn || !$employee->shift || !$employee->shift->start_time) {
            return 0;
        }
        
        $checkInTime = Carbon::parse($date->format('Y-m-d') . ' ' . $checkIn->time);
        $shiftStart = Carbon::parse($date->format('Y-m-d') . ' ' . $employee->shift->start_time);

        return (int) max(0, abs($shiftStart->diffInMinutes($checkInTime)));
    }
    
    /**
     * Check if employee left early
     */
    private function isEarlyDeparture(Employee $employee, ?Attendance $checkOut, Carbon $date): bool
    {
        if (!$checkOut || !$employee->shift || !$employee->shift->end_time) {
            return false;
        }

        // For overnight shifts the shift end is on the next calendar day.
        $checkOutDate = $date->copy();
        if ($employee->shift->isOvernightShift()) {
            $checkOutDate->addDay();
        }

        $checkOutTime = $checkOutDate->copy()->setTimeFromTimeString($checkOut->time);
        $shiftEnd     = $employee->shift->getShiftEndDatetime($date);

        // Add allowed early leave minutes if configured
        $allowedEarlyLeaveMinutes = $employee->shift->allowed_early_leave_minutes ?? 0;
        $shiftEnd->subMinutes($allowedEarlyLeaveMinutes);

        return $checkOutTime->lt($shiftEnd);
    }

    /**
     * Calculate early departure minutes
     */
    private function calculateEarlyMinutes(Employee $employee, ?Attendance $checkOut, Carbon $date): int
    {
        if (!$checkOut || !$employee->shift || !$employee->shift->end_time) {
            return 0;
        }

        // For overnight shifts the shift end is on the next calendar day.
        $checkOutDate = $date->copy();
        if ($employee->shift->isOvernightShift()) {
            $checkOutDate->addDay();
        }

        $checkOutTime = $checkOutDate->copy()->setTimeFromTimeString($checkOut->time);
        $shiftEnd     = $employee->shift->getShiftEndDatetime($date);

        return (int) max(0, abs($checkOutTime->diffInMinutes($shiftEnd)));
    }
    
    /**
     * Get pending leave requests that overlap with the date
     */
    private function getPendingLeaveRequests(Carbon $date, ?int $departmentId = null): Collection
    {
        $query = LeaveRequest::whereIn('status', ['submitted', 'draft'])
            ->where(function ($q) use ($date) {
                $q->whereDate('start_date', '<=', $date->format('Y-m-d'))
                    ->whereDate('end_date', '>=', $date->format('Y-m-d'));
            })
            ->with(['employee.department', 'leaveType']);
        
        if ($departmentId) {
            $query->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }
        
        return $query->get();
    }
}
