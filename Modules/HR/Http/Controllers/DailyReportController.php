<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HR\Models\Attendance;
use Modules\HR\Models\Department;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeeAdvance;
use Modules\HR\Models\EmployeeDeductionReward;
use Modules\HR\Models\Errand;
use Modules\HR\Models\FlexibleSalaryProcessing;
use Modules\HR\Models\LeaveRequest;
use Modules\HR\Models\WorkPermission;
use Modules\HR\Services\DailyEmployeeReportService;
use Modules\HR\Services\OvernightAttendanceService;
use Modules\HR\Services\SalaryCalculationService;

class DailyReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view Attendances');
    }

    public function index(Request $request, DailyEmployeeReportService $service): View
    {
        $dateFrom     = $request->input('date_from', now()->format('Y-m-d'));
        $dateTo       = $request->input('date_to', $dateFrom);
        $departmentId = $request->input('department_id') ? (int) $request->input('department_id') : null;
        $employeeId   = $request->input('employee_id') ? (int) $request->input('employee_id') : null;
        $searchName   = $request->input('search_name', '');

        $departments = Department::orderBy('title')->get();

        $employeesQuery = Employee::where('status', '!=', 'خارج الشركة')
            ->orderBy('name');

        if ($departmentId) {
            $employeesQuery->where('department_id', $departmentId);
        }

        if ($searchName) {
            $employeesQuery->where('name', 'like', '%' . $searchName . '%');
        }

        $employees = $employeesQuery->get();

        $reportData = $service->generateDailyReport($dateFrom, $dateTo, $departmentId, $employeeId, $searchName);

        return view('hr::hr-management.attendances.reports.daily-report', compact(
            'reportData',
            'departments',
            'employees',
            'dateFrom',
            'dateTo',
            'departmentId',
            'employeeId',
            'searchName'
        ));
    }

    public function employeeComprehensiveReport(
        Request $request,
        Employee $employee,
        SalaryCalculationService $salaryCalculationService
    ): View {
        $dateFrom = $request->input('date_from', now()->format('Y-m-d'));
        $dateTo   = $request->input('date_to', $dateFrom);

        $from = Carbon::parse($dateFrom)->startOfDay();
        $to   = Carbon::parse($dateTo)->startOfDay();

        if ($from->gt($to)) { [$from, $to] = [$to, $from]; }

        // Limit range to max 90 days
        if ($from->diffInDays($to) > 90) {
            $to = $from->copy()->addDays(90);
        }

        $employee->load(['department', 'job', 'shift']);

        // Project codes assigned to the employee (employee_project pivot)
        $assignedProjectCodes = '';
        if (Schema::hasTable('employee_project')) {
            $assignedProjectCodes = DB::table('employee_project')
                ->join('projects', 'projects.id', '=', 'employee_project.project_id')
                ->where('employee_project.employee_id', $employee->id)
                ->orderBy('projects.project_code')
                ->pluck('projects.project_code')
                ->filter()
                ->unique()
                ->implode(' | ');
        }

        // ── Bulk-load raw data ───────────────────────────────────────────────
        // Extend the fetch window one day past $to for overnight shifts so exits
        // punched after midnight can be re-attributed to their work day.
        $fetchTo = ($employee->shift && $employee->shift->isOvernightShift())
            ? $to->copy()->addDay()
            : $to->copy();

        $allAttendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$from->toDateString(), $fetchTo->toDateString()])
            ->orderBy('time')
            ->get();

        // Group by work day (post-midnight check-outs belong to the previous
        // day's shift) — same rule the salary engine uses internally.
        $allAttendances = collect(OvernightAttendanceService::groupByWorkday(
            $allAttendances,
            $employee->shift
        ));

        $leaves = LeaveRequest::where('employee_id', $employee->id)
            ->where(fn ($q) => $q
                ->whereBetween('start_date', [$from->toDateString(), $to->toDateString()])
                ->orWhereBetween('end_date',  [$from->toDateString(), $to->toDateString()])
                ->orWhere(fn ($q2) => $q2
                    ->where('start_date', '<=', $from->toDateString())
                    ->where('end_date',   '>=', $to->toDateString())))
            ->with('leaveType')
            ->orderBy('start_date', 'desc')
            ->get();

        $errands = Errand::where('employee_id', $employee->id)
            ->where(fn ($q) => $q
                ->whereBetween('start_date', [$from->toDateString(), $to->toDateString()])
                ->orWhereBetween('end_date',  [$from->toDateString(), $to->toDateString()])
                ->orWhere(fn ($q2) => $q2
                    ->where('start_date', '<=', $from->toDateString())
                    ->where('end_date',   '>=', $to->toDateString())))
            ->orderBy('start_date', 'desc')
            ->get();

        $workPermissions = WorkPermission::where('employee_id', $employee->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date', 'desc')
            ->get();

        $advances = EmployeeAdvance::where('employee_id', $employee->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date', 'desc')
            ->get();

        $deductionsRewards = EmployeeDeductionReward::where('employee_id', $employee->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date', 'desc')
            ->get();

        $flexibleSalaryProcessings = FlexibleSalaryProcessing::where('employee_id', $employee->id)
            ->where(fn ($q) => $q
                ->whereBetween('period_start', [$from->toDateString(), $to->toDateString()])
                ->orWhereBetween('period_end',  [$from->toDateString(), $to->toDateString()]))
            ->orderBy('period_start', 'desc')
            ->get();

        // Day rows and day-off numbers come from the salary engine so the report
        // cannot label a different day as rest than the one that was paid.
        $salaryCalcResult = $salaryCalculationService->calculateSalary($employee, $from, $to);
        $salaryData       = $salaryCalcResult['salary_data'];
        $calcSummary      = $salaryCalcResult['summary'] ?? [];
        $details          = $salaryCalcResult['details'] ?? [];

        $dailyRecords = [];
        $totalEarlyMinutes = 0;
        $presentDaysCount = 0;
        $leaveDaysCount = 0;
        $errandDaysCount = 0;
        $permissionDaysCount = 0;

        foreach ($details as $dateStr => $day) {
            $currentDate = Carbon::parse((string) $dateStr);
            $viewStatus = $this->mapSalaryDayStatus((string) ($day['status'] ?? ''));
            $actualHours = (float) ($day['actual_hours'] ?? 0);
            $dayAttendances = $allAttendances->get($dateStr, collect());
            $earlyMinutes = $this->earlyLeaveMinutes($employee, (string) $dateStr, $dayAttendances);
            $totalEarlyMinutes += $earlyMinutes;

            $firstCheckIn = $dayAttendances->where('type', 'check_in')->sortBy('time')->first();
            $lastCheckOut = $dayAttendances->where('type', 'check_out')->sortByDesc('time')->first();

            // Project code recorded on the check-in fingerprint for that day
            $dayProjectCode = $firstCheckIn?->project_code;

            // The salary engine may omit actual times on non-working days (e.g. day off)
            // — fall back to the raw fingerprint records so real check-ins are never hidden.
            $checkIn  = ($day['check_in_time']  ?? null) ?: $firstCheckIn?->time;
            $checkOut = ($day['check_out_time'] ?? null) ?: $lastCheckOut?->time;

            if ($viewStatus === 'present') {
                $presentDaysCount++;
            } elseif ($viewStatus === 'leave') {
                $leaveDaysCount++;
            } elseif ($viewStatus === 'errand') {
                $errandDaysCount++;
            } elseif ($viewStatus === 'permission') {
                $permissionDaysCount++;
            }

            $dailyRecords[] = [
                'date' => (string) $dateStr,
                'day_name' => $currentDate->translatedFormat('l'),
                'date_formatted' => $currentDate->translatedFormat('d F Y'),
                'status' => $viewStatus,
                'project_code' => $dayProjectCode ?: '-',
                'is_working' => ($day['day_type'] ?? '') === 'working_day',
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'work_hours' => round($actualHours, 2),
                'work_minutes' => (int) round($actualHours * 60),
                'late_minutes' => (int) ($day['late_minutes'] ?? 0),
                'early_minutes' => $earlyMinutes,
                'notes' => (string) ($day['notes'] ?? ''),
            ];
        }

        // ── KPIs ─────────────────────────────────────────────────────────────
        $totalAdvances   = $advances->where('status', 'approved')->sum('amount');
        $totalDeductions = $deductionsRewards->where('type', 'deduction')->sum('amount');
        $totalRewards    = $deductionsRewards->where('type', 'reward')->sum('amount');

        $kpis = [
            'total_present_days'   => $presentDaysCount,
            'total_absent_days'    => (int) ($calcSummary['absent_days'] ?? 0),
            'total_leave_days'     => $leaveDaysCount,
            'total_errands'        => $errandDaysCount,
            'total_permissions'    => $permissionDaysCount,
            'total_day_off_used'   => (int) ($calcSummary['day_off_used'] ?? 0),
            'total_extra_days'     => (int) ($calcSummary['extra_days'] ?? 0),
            'total_work_hours'     => round((float) ($calcSummary['actual_hours'] ?? 0), 1),
            'total_late_minutes'   => (int) ($calcSummary['late_minutes'] ?? 0),
            'total_early_minutes'  => $totalEarlyMinutes,
            'total_advances'       => (float) $totalAdvances,
            'total_deductions'     => (float) $totalDeductions,
            'total_rewards'        => (float) $totalRewards,
        ];

        $salaryDeductions = (float) ($salaryData['unpaid_leave_deduction'] ?? 0)
            + (float) ($salaryData['absent_days_deduction'] ?? 0)
            + (float) ($salaryData['late_hours_deduction'] ?? 0);

        $shiftHours = 8.0;
        if ($employee->shift?->start_time && $employee->shift?->end_time) {
            // Same logic as SalaryCalculationService::getExpectedHours(): anchor both
            // times on a fixed date and push the end to the next day for overnight
            // shifts, otherwise Carbon 3's signed diff returns negative hours.
            $refDate = Carbon::parse('2000-01-01');
            $shiftStart = $refDate->copy()->setTimeFromTimeString($employee->shift->start_time);
            $shiftEnd = $refDate->copy()->setTimeFromTimeString($employee->shift->end_time);
            if ($employee->shift->isOvernightShift()) {
                $shiftEnd->addDay();
            }
            $shiftHours = (int) round($shiftStart->diffInMinutes($shiftEnd)) / 60;
        }

        $toHHMM = static function (int $totalMinutes): string {
            $h = intdiv(abs($totalMinutes), 60);
            $m = abs($totalMinutes) % 60;
            return sprintf('%02d:%02d', $h, $m);
        };

        $workingDays = (int) ($calcSummary['working_days'] ?? 0);
        $overtimeMinutes = (int) ($calcSummary['overtime_minutes'] ?? 0);
        $lateMinsForCalc = (int) ($calcSummary['late_minutes'] ?? 0);
        $presentForCalc = (float) ($calcSummary['present_days'] ?? 0);
        $requiredMinutes = (int) round($workingDays * $shiftHours * 60);
        $actualMinutes = max(0, (int) round($presentForCalc * $shiftHours * 60) - $lateMinsForCalc);
        $deficitMinutes = max(0, $requiredMinutes - $actualMinutes);

        $hoursKpis = [
            'required_hhmm'  => $toHHMM($requiredMinutes),
            'actual_hhmm'    => $toHHMM($actualMinutes),
            'overtime_hhmm'  => $toHHMM($overtimeMinutes),
            'deficit_hhmm'   => $toHHMM($deficitMinutes),
            'working_days'   => $workingDays,
            'day_off_used'   => (int) ($calcSummary['day_off_used'] ?? 0),
            'has_deficit'    => $deficitMinutes > 0,
            'has_overtime'   => $overtimeMinutes > 0,
        ];

        return view('hr::hr-management.attendances.reports.employee-comprehensive-report', compact(
            'employee', 'dateFrom', 'dateTo',
            'kpis', 'hoursKpis',
            'dailyRecords', 'leaves', 'errands',
            'workPermissions', 'advances', 'deductionsRewards',
            'flexibleSalaryProcessings', 'salaryData', 'salaryDeductions'
        ) + [
            'projectCode' => $assignedProjectCodes,
        ]);
    }

    private function mapSalaryDayStatus(string $status): string
    {
        return match ($status) {
            'present', 'half_day' => 'present',
            'absent' => 'absent',
            'day_off' => 'day_off',
            'paid_leave', 'unpaid_leave' => 'leave',
            'errand' => 'errand',
            'permission' => 'permission',
            default => 'weekend',
        };
    }

    private function earlyLeaveMinutes(Employee $employee, string $dateStr, Collection $dayAttendances): int
    {
        $lastCheckOut = $dayAttendances->where('type', 'check_out')->sortByDesc('time')->first();
        if (! $employee->shift?->end_time || ! $lastCheckOut) {
            return 0;
        }

        $shiftEnd = Carbon::parse($dateStr.' '.$employee->shift->end_time);

        // Overnight shift: the exit belongs to the work day but the shift ends
        // on the next calendar day, so anchor the end accordingly.
        if ($employee->shift->isOvernightShift()) {
            $shiftEnd->addDay();
        }

        $allowedEarly = (int) ($employee->shift->allowed_early_leave_minutes ?? 0);
        $actualOut = Carbon::parse($dateStr.' '.$lastCheckOut->time);

        // Time inside the post-midnight segment of the overnight shift (before
        // the next shift start) means the punch belongs to this work day's end.
        if ($employee->shift->isOvernightShift()
            && $employee->shift->start_time
            && $lastCheckOut->time < $employee->shift->start_time) {
            $actualOut->addDay();
        }
        if ($actualOut->lt($shiftEnd->copy()->subMinutes($allowedEarly))) {
            return (int) $actualOut->diffInMinutes($shiftEnd);
        }

        return 0;
    }
}

