<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Modules\HR\Models\Attendance;
use Modules\HR\Models\Employee;

class EmployeeFingerprintsReportController extends Controller
{
    private const MAX_RANGE_DAYS = 90;

    public function __construct()
    {
        $this->middleware('can:view Attendances');
    }

    public function index(Request $request): View
    {
        $dateFrom   = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo     = $request->input('date_to', now()->format('Y-m-d'));
        $employeeId = $request->filled('employee_id') ? (int) $request->input('employee_id') : null;

        $from = Carbon::parse($dateFrom)->startOfDay();
        $to   = Carbon::parse($dateTo)->startOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy(), $from->copy()];
            $dateFrom    = $from->format('Y-m-d');
            $dateTo      = $to->format('Y-m-d');
        }

        if ($from->diffInDays($to) > self::MAX_RANGE_DAYS) {
            $to      = $from->copy()->addDays(self::MAX_RANGE_DAYS);
            $dateTo  = $to->format('Y-m-d');
        }

        $employees = Employee::query()
            ->where('status', '!=', 'خارج الشركة')
            ->orderBy('name')
            ->get(['id', 'name']);

        $employee     = $employeeId ? Employee::query()->find($employeeId) : null;
        $days         = collect();
        $totalPunches = 0;

        if ($employee) {
            $attendances = Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('date', '>=', $dateFrom)
                ->whereDate('date', '<=', $dateTo)
                ->orderBy('date')
                ->orderBy('time')
                ->get();

            $totalPunches = $attendances->count();
            $days         = $this->groupByDate($attendances);
        }

        return view('hr::hr-management.attendances.reports.employee-fingerprints-report', compact(
            'employees',
            'employee',
            'employeeId',
            'dateFrom',
            'dateTo',
            'days',
            'totalPunches'
        ));
    }

    /**
     * @return Collection<string, Collection<int, Attendance>>
     */
    private function groupByDate(Collection $attendances): Collection
    {
        return $attendances
            ->groupBy(function (Attendance $attendance) {
                $date = $attendance->date;

                return $date instanceof Carbon
                    ? $date->format('Y-m-d')
                    : Carbon::parse($date)->format('Y-m-d');
            })
            ->sortKeysDesc();
    }
}
