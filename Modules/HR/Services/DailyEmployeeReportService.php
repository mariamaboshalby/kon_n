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
     * @return array
     */
    public function generateDailyReport(
        string $dateFrom,
        string $dateTo,
        ?int $departmentId = null,
        ?int $employeeId = null,
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
     * Get attendance records for employee on specific date
     */
    private function getAttendanceRecords(Employee $employee, Carbon $date): Collection
    {
        return Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $date->format('Y-m-d'))
            ->orderBy('time')
            ->get();
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
        
        return (int) max(0, $shiftStart->diffInMinutes($checkInTime));
    }
    
    /**
     * Check if employee left early
     */
    private function isEarlyDeparture(Employee $employee, ?Attendance $checkOut, Carbon $date): bool
    {
        if (!$checkOut || !$employee->shift || !$employee->shift->end_time) {
            return false;
        }
        
        $checkOutTime = Carbon::parse($date->format('Y-m-d') . ' ' . $checkOut->time);
        $shiftEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $employee->shift->end_time);
        
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
        
        $checkOutTime = Carbon::parse($date->format('Y-m-d') . ' ' . $checkOut->time);
        $shiftEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $employee->shift->end_time);
        
        return (int) max(0, $checkOutTime->diffInMinutes($shiftEnd));
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
