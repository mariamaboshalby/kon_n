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

class MonthlyActivitySummaryService
{
    /**
     * Generate an activity summary for managers/stakeholders over a date range.
     *
     * Logic mirrors DailyEmployeeReportService: for each working day we classify
     * each employee as present / absent / on_leave / on_errand / with_permission,
     * then aggregate the counts per employee.
     *
     * @param string   $dateFrom  Y-m-d
     * @param string   $dateTo    Y-m-d
     * @param int|null $departmentId
     * @param string   $searchName Employee name search filter
     * @return array
     */
    public function generate(string $dateFrom, string $dateTo, ?int $departmentId = null, string $searchName = ''): array
    {
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to   = Carbon::parse($dateTo)->startOfDay();

        // ── Employees ────────────────────────────────────────────────────────
        $employees = Employee::where('status', '!=', 'خارج الشركة')
            ->with(['department', 'shift'])
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->when($searchName, fn ($q) => $q->where('name', 'like', '%' . $searchName . '%'))
            ->orderBy('name')
            ->get();

        $employeeIds = $employees->pluck('id')->all();

        // ── Bulk-load all raw data for the period (avoid N+1) ─────────────────
        $allAttendances = Attendance::whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('time')
            ->get()
            ->groupBy(fn ($a) => $a->employee_id . '_' . $a->date->format('Y-m-d'));

        $allLeaves = LeaveRequest::whereIn('employee_id', $employeeIds)
            ->where('status', 'approved')
            ->where(fn ($q) => $q
                ->whereBetween('start_date', [$from->toDateString(), $to->toDateString()])
                ->orWhereBetween('end_date', [$from->toDateString(), $to->toDateString()])
                ->orWhere(fn ($q2) => $q2
                    ->where('start_date', '<=', $from->toDateString())
                    ->where('end_date', '>=', $to->toDateString())
                )
            )
            ->get()
            ->groupBy('employee_id');

        $allErrands = Errand::whereIn('employee_id', $employeeIds)
            ->whereNotNull('approved_at')
            ->where(fn ($q) => $q
                ->whereBetween('start_date', [$from->toDateString(), $to->toDateString()])
                ->orWhereBetween('end_date', [$from->toDateString(), $to->toDateString()])
                ->orWhere(fn ($q2) => $q2
                    ->where('start_date', '<=', $from->toDateString())
                    ->where('end_date', '>=', $to->toDateString())
                )
            )
            ->get()
            ->groupBy('employee_id');

        $allPermissions = WorkPermission::whereIn('employee_id', $employeeIds)
            ->where('status', 'approved')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy(fn ($p) => $p->employee_id . '_' . $p->date->format('Y-m-d'));

        // ── Build working-day list ────────────────────────────────────────────
        $workingDays = $this->buildWorkingDays($from, $to);
        $workingDayCount = count($workingDays);

        // ── Per-employee aggregation ──────────────────────────────────────────
        $rows = [];

        foreach ($employees as $employee) {
            $empLeaves      = $allLeaves->get($employee->id, collect());
            $empErrands     = $allErrands->get($employee->id, collect());

            $presentDays    = 0;
            $absentDays     = 0;
            $leaveDays      = 0;
            $errandDays     = 0;
            $permissionDays = 0;
            $lateDays       = 0;

            foreach ($workingDays as $day) {
                $dayStr = $day->format('Y-m-d');
                $key    = $employee->id . '_' . $dayStr;

                // 1. On approved leave?
                $onLeave = $empLeaves->first(
                    fn ($l) => $l->start_date->lte($day) && $l->end_date->gte($day)
                );
                if ($onLeave) {
                    $leaveDays++;
                    continue;
                }

                // 2. On approved errand?
                $onErrand = $empErrands->first(
                    fn ($e) => Carbon::parse($e->start_date)->lte($day)
                            && Carbon::parse($e->end_date)->gte($day)
                );
                if ($onErrand) {
                    $errandDays++;
                    continue;
                }

                // 3. Has approved work permission for this day?
                if ($allPermissions->has($key)) {
                    $permissionDays++;
                    // Permission = present (employee came in, just left early)
                    $presentDays++;
                    continue;
                }

                // 4. Has attendance records?
                $dayAttendances = $allAttendances->get($key, collect());

                if ($dayAttendances->isNotEmpty()) {
                    $presentDays++;

                    // Check lateness using shift start time (same logic as DailyEmployeeReportService)
                    $checkIn = $dayAttendances->where('type', 'check_in')->sortBy('time')->first();
                    if ($this->isLate($employee, $checkIn, $day)) {
                        $lateDays++;
                    }
                } else {
                    $absentDays++;
                }
            }

            $attendancePct = $workingDayCount > 0
                ? round(($presentDays / $workingDayCount) * 100, 1)
                : 0.0;

            $rows[] = [
                'employee_id'      => $employee->id,
                'employee_name'    => $employee->name,
                'department'       => $employee->department?->title ?? '-',
                'department_id'    => $employee->department_id,
                'present_days'     => $presentDays,
                'absent_days'      => $absentDays,
                'leave_days'       => $leaveDays,
                'errand_days'      => $errandDays,
                'permission_count' => $permissionDays,
                'late_days'        => $lateDays,
                'attendance_pct'   => $attendancePct,
            ];
        }

        // ── Department totals ─────────────────────────────────────────────────
        $deptTotals = collect($rows)
            ->groupBy('department')
            ->map(fn (Collection $group, string $dept) => [
                'department'         => $dept,
                'employee_count'     => $group->count(),
                'avg_present'        => round($group->avg('present_days'), 1),
                'avg_absent'         => round($group->avg('absent_days'), 1),
                'avg_leave'          => round($group->avg('leave_days'), 1),
                'avg_attendance_pct' => round($group->avg('attendance_pct'), 1),
                'total_late'         => $group->sum('late_days'),
                'total_permissions'  => $group->sum('permission_count'),
            ])
            ->values()
            ->all();

        // ── Overall summary ───────────────────────────────────────────────────
        $rowsCollection  = collect($rows);
        $totalEmployees  = count($rows);

        $summary = [
            'total_employees'    => $totalEmployees,
            'working_days'       => $workingDayCount,
            'avg_attendance_pct' => $totalEmployees > 0
                ? round($rowsCollection->avg('attendance_pct'), 1)
                : 0.0,
            'total_present_days' => $rowsCollection->sum('present_days'),
            'total_absent_days'  => $rowsCollection->sum('absent_days'),
            'total_leave_days'   => $rowsCollection->sum('leave_days'),
            'total_errand_days'  => $rowsCollection->sum('errand_days'),
            'total_permissions'  => $rowsCollection->sum('permission_count'),
            'total_late_days'    => $rowsCollection->sum('late_days'),
            'total_work_hours'   => $rowsCollection->sum('present_days') * 8,
            'perfect_attendance' => $rowsCollection
                ->filter(fn ($r) => $r['absent_days'] === 0 && $r['late_days'] === 0)
                ->count(),
        ];

        return [
            'date_from'    => $from->toDateString(),
            'date_to'      => $to->toDateString(),
            'period_label' => $from->isSameDay($to)
                ? $from->translatedFormat('d F Y')
                : $from->translatedFormat('d F Y') . ' — ' . $to->translatedFormat('d F Y'),
            'working_days' => $workingDayCount,
            'summary'      => $summary,
            'rows'         => $rows,
            'dept_totals'  => $deptTotals,
        ];
    }

    /**
     * Return a list of Carbon instances for each working day (Mon–Fri) in the range.
     *
     * @return Carbon[]
     */
    private function buildWorkingDays(Carbon $from, Carbon $to): array
    {
        $days    = [];
        $current = $from->copy();

        while ($current->lte($to)) {
            if (!$current->isWeekend()) {
                $days[] = $current->copy();
            }
            $current->addDay();
        }

        return $days;
    }

    /**
     * Check if the employee's check-in is late relative to their shift start.
     * Mirrors the logic in DailyEmployeeReportService::isLate().
     */
    private function isLate(Employee $employee, ?Attendance $checkIn, Carbon $date): bool
    {
        if (!$checkIn || !$employee->shift || !$employee->shift->start_time) {
            return false;
        }

        $checkInTime  = Carbon::parse($date->format('Y-m-d') . ' ' . $checkIn->time);
        $shiftStart   = Carbon::parse($date->format('Y-m-d') . ' ' . $employee->shift->start_time);
        $allowedLate  = $employee->shift->allowed_late_minutes ?? 0;
        $shiftStart->addMinutes((int) $allowedLate);

        return $checkInTime->gt($shiftStart);
    }
}
