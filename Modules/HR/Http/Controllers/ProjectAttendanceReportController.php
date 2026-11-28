<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HR\Models\AttendanceProcessingDetail;
use Modules\HR\Models\Department;
use Modules\HR\Models\Employee;
use Modules\Projects\Models\Project;

class ProjectAttendanceReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view Attendance Processing');
    }

    public function index(Request $request): View
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $projectCode = $request->string('project_code')->toString() ?: null;
        $employeeId = $request->integer('employee_id') ?: null;
        $departmentId = $request->integer('department_id') ?: null;

        $query = AttendanceProcessingDetail::query()
            ->with(['employee:id,name', 'department:id,title'])
            ->whereBetween('attendance_date', [$startDate, $endDate]);

        if ($projectCode) {
            $project = Project::query()
                ->where('project_code', $projectCode)
                ->orWhere('name', $projectCode)
                ->first();
            if ($project) {
                $query->where(function ($q) use ($project) {
                    $q->where('project_code', $project->project_code)
                        ->orWhere('project_code', $project->name);
                });
            } else {
                $query->where('project_code', $projectCode);
            }
        }
        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $rows = $query->orderByDesc('attendance_date')->limit(500)->get();
        $summary = [
            'total_hours' => (float) $rows->sum('attendance_actual_hours_count'),
            'total_overtime_minutes' => (float) $rows->sum('attendance_overtime_minutes_count'),
            'total_cost' => (float) $rows->sum('total_due_hourly_salary'),
            'rows_count' => $rows->count(),
        ];

        $projectCodes = AttendanceProcessingDetail::query()
            ->whereNotNull('project_code')
            ->distinct()
            ->orderBy('project_code')
            ->pluck('project_code');

        return view('hr::hr-management.attendances.reports.project-attendance-report', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'projectCode' => $projectCode,
            'employeeId' => $employeeId,
            'departmentId' => $departmentId,
            'rows' => $rows,
            'summary' => $summary,
            'projectCodes' => $projectCodes,
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }
}
