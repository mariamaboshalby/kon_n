<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HR\Models\Department;
use Modules\HR\Models\Employee;
use Modules\HR\Services\DailyEmployeeReportService;

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

        $departments = Department::orderBy('title')->get();

        $employeesQuery = Employee::where('status', '!=', 'خارج الشركة')
            ->orderBy('name');

        if ($departmentId) {
            $employeesQuery->where('department_id', $departmentId);
        }

        $employees = $employeesQuery->get();

        $reportData = $service->generateDailyReport($dateFrom, $dateTo, $departmentId, $employeeId);

        return view('hr::hr-management.attendances.reports.daily-report', compact(
            'reportData',
            'departments',
            'employees',
            'dateFrom',
            'dateTo',
            'departmentId',
            'employeeId'
        ));
    }
}
