<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\HR\Models\Employee;

class EmployeeStatisticsService
{
    private const STATUS_KEYS = [
        'مقيم' => 'resident',
        'مواطن' => 'citizen',
        'زائر' => 'visitor',
        'خارج الشركة' => 'outside_company',
    ];

    /**
     * Aggregate employee KPIs and chart datasets for the employees index.
     * Respects Employee BranchScope via Eloquent queries.
     *
     * @return array{
     *     kpis: array<string, int>,
     *     by_status: array{labels: list<string>, keys: list<string>, values: list<int>},
     *     by_department: array{labels: list<string>, values: list<int>},
     *     monthly: array{labels: list<string>, hires: list<int>, exits: list<int>}
     * }
     */
    public function getDashboardData(): array
    {
        $now = now();

        return [
            'kpis' => $this->buildKpis($now),
            'by_status' => $this->buildStatusDistribution(),
            'by_department' => $this->buildDepartmentDistribution(),
            'monthly' => $this->buildMonthlyHiresAndExits(12, $now),
        ];
    }

    /**
     * @return array{total: int, currently_employed: int, hired_this_month: int, left_this_month: int}
     */
    private function buildKpis(Carbon $now): array
    {
        $base = Employee::query();

        $total = (clone $base)->count();

        $currentlyEmployed = (clone $base)
            ->whereNull('date_of_fire')
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhere('status', '!=', 'خارج الشركة');
            })
            ->count();

        $hiredThisMonth = (clone $base)
            ->where(function ($q) {
                $q->whereNotNull('date_of_hire')
                    ->orWhereNotNull('date_of_joining');
            })
            ->where(function ($q) use ($now) {
                $q->where(function ($inner) use ($now) {
                    $inner->whereNotNull('date_of_hire')
                        ->whereYear('date_of_hire', $now->year)
                        ->whereMonth('date_of_hire', $now->month);
                })->orWhere(function ($inner) use ($now) {
                    $inner->whereNull('date_of_hire')
                        ->whereNotNull('date_of_joining')
                        ->whereYear('date_of_joining', $now->year)
                        ->whereMonth('date_of_joining', $now->month);
                });
            })
            ->count();

        $leftThisMonth = (clone $base)
            ->whereNotNull('date_of_fire')
            ->whereYear('date_of_fire', $now->year)
            ->whereMonth('date_of_fire', $now->month)
            ->count();

        return [
            'total' => $total,
            'currently_employed' => $currentlyEmployed,
            'hired_this_month' => $hiredThisMonth,
            'left_this_month' => $leftThisMonth,
        ];
    }

    /**
     * @return array{labels: list<string>, keys: list<string>, values: list<int>}
     */
    private function buildStatusDistribution(): array
    {
        $rows = Employee::query()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->orderByDesc('aggregate')
            ->get();

        $labels = [];
        $keys = [];
        $values = [];

        foreach ($rows as $row) {
            $raw = $row->getRawOriginal('status');
            $key = self::STATUS_KEYS[$raw] ?? null;
            $keys[] = $key ?? 'status_unknown';
            $labels[] = $key
                ? __('hr.'.$key)
                : ($raw ? (string) $raw : __('hr.status_unknown'));
            $values[] = (int) $row->aggregate;
        }

        return [
            'labels' => $labels,
            'keys' => $keys,
            'values' => $values,
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    private function buildDepartmentDistribution(int $limit = 10): array
    {
        $rows = Employee::query()
            ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
            ->select(
                'employees.department_id',
                DB::raw('MAX(departments.title) as department_title'),
                DB::raw('COUNT(employees.id) as aggregate')
            )
            ->groupBy('employees.department_id')
            ->orderByDesc('aggregate')
            ->limit($limit)
            ->get();

        $labels = [];
        $values = [];

        foreach ($rows as $row) {
            $labels[] = $row->department_title
                ?: __('hr.no_department');
            $values[] = (int) $row->aggregate;
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * @return array{labels: list<string>, hires: list<int>, exits: list<int>}
     */
    private function buildMonthlyHiresAndExits(int $months, Carbon $now): array
    {
        $start = $now->copy()->startOfMonth()->subMonths($months - 1);

        $hiresRaw = Employee::query()
            ->where(function ($q) {
                $q->whereNotNull('date_of_hire')
                    ->orWhereNotNull('date_of_joining');
            })
            ->whereRaw('COALESCE(date_of_hire, date_of_joining) >= ?', [$start->toDateString()])
            ->select(
                DB::raw("DATE_FORMAT(COALESCE(date_of_hire, date_of_joining), '%Y-%m') as period"),
                DB::raw('COUNT(*) as aggregate')
            )
            ->groupBy(DB::raw("DATE_FORMAT(COALESCE(date_of_hire, date_of_joining), '%Y-%m')"))
            ->pluck('aggregate', 'period');

        $exitsRaw = Employee::query()
            ->whereNotNull('date_of_fire')
            ->where('date_of_fire', '>=', $start->toDateString())
            ->select(
                DB::raw("DATE_FORMAT(date_of_fire, '%Y-%m') as period"),
                DB::raw('COUNT(*) as aggregate')
            )
            ->groupBy(DB::raw("DATE_FORMAT(date_of_fire, '%Y-%m')"))
            ->pluck('aggregate', 'period');

        $labels = [];
        $hires = [];
        $exits = [];

        $cursor = $start->copy();
        for ($i = 0; $i < $months; $i++) {
            $key = $cursor->format('Y-m');
            $labels[] = $cursor->translatedFormat('M Y');
            $hires[] = (int) ($hiresRaw[$key] ?? 0);
            $exits[] = (int) ($exitsRaw[$key] ?? 0);
            $cursor->addMonth();
        }

        return [
            'labels' => $labels,
            'hires' => $hires,
            'exits' => $exits,
        ];
    }
}
