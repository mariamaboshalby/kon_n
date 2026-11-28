<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HR\Models\Department;
use Modules\HR\Services\MonthlyActivitySummaryService;

class MonthlyActivityReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view Attendances');
    }

    public function index(Request $request, MonthlyActivitySummaryService $service): View
    {
        $dateFrom     = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo       = $request->input('date_to',   now()->format('Y-m-d'));
        $departmentId = $request->input('department_id') ? (int) $request->input('department_id') : null;
        $searchName   = $request->input('search_name', '');

        $departments = Department::orderBy('title')->get();
        $reportData  = $service->generate($dateFrom, $dateTo, $departmentId, $searchName);

        return view('hr::hr-management.attendances.reports.monthly-activity-report', compact(
            'reportData',
            'departments',
            'dateFrom',
            'dateTo',
            'departmentId',
            'searchName'
        ));
    }
}
