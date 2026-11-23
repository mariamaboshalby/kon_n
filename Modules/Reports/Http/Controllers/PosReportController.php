<?php

declare(strict_types=1);

namespace Modules\Reports\Http\Controllers;

use App\Models\OperHead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class PosReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view POS Reports');
    }

    public function peakHours(Request $request): View
    {
        $year  = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);

        $data = $this->getPeakHoursData($year, $month);

        return view('reports::sales.peak-hours-calender', array_merge($data, [
            'year'  => $year,
            'month' => $month,
        ]));
    }

    public function peakHoursDay(Request $request): JsonResponse
    {
        $date = $request->validate(['date' => ['required', 'date_format:Y-m-d']])['date'];

        return response()->json(['hours' => $this->getHourlyOrdersForDay($date)]);
    }

    /**
     * @return array{calendar: array, days_in_month: int, first_day_of_week: int, stats: array}
     */
    private function getPeakHoursData(int $year, int $month): array
    {
        $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate   = $startDate->copy()->endOfMonth();

        $dailyCounts = OperHead::query()
            ->where('pro_type', 102)
            ->where('isdeleted', 0)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as orders_count')
            ->groupBy('day')
            ->pluck('orders_count', 'day')
            ->toArray();

        $maxDaily    = ! empty($dailyCounts) ? max($dailyCounts) : 1;
        $daysInMonth = $startDate->daysInMonth;
        $calendar    = [];

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateKey  = $startDate->copy()->day($d)->format('Y-m-d');
            $count    = $dailyCounts[$dateKey] ?? 0;
            $calendar[] = [
                'day'          => $d,
                'date'         => $dateKey,
                'orders_count' => $count,
                'ratio'        => $maxDaily > 0 ? round($count / $maxDaily, 2) : 0,
            ];
        }

        $totalOrders = array_sum($dailyCounts);
        $busiestDay  = ! empty($dailyCounts) ? array_search(max($dailyCounts), $dailyCounts) : null;

        $hourlyAll = OperHead::query()
            ->where('pro_type', 102)
            ->where('isdeleted', 0)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as cnt')
            ->groupBy('hour')
            ->orderByDesc('cnt')
            ->first();

        return [
            'calendar'          => $calendar,
            'days_in_month'     => $daysInMonth,
            'first_day_of_week' => (int) $startDate->dayOfWeek,
            'stats'             => [
                'total_orders' => $totalOrders,
                'avg_per_day'  => $daysInMonth > 0 ? round($totalOrders / $daysInMonth, 1) : 0,
                'busiest_day'  => $busiestDay,
                'busiest_hour' => $hourlyAll ? (int) $hourlyAll->hour : null,
                'max_daily'    => $maxDaily,
            ],
        ];
    }

    /** @return array<int, int> */
    private function getHourlyOrdersForDay(string $date): array
    {
        $counts = OperHead::query()
            ->where('pro_type', 102)
            ->where('isdeleted', 0)
            ->whereDate('created_at', $date)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as cnt')
            ->groupBy('hour')
            ->pluck('cnt', 'hour')
            ->toArray();

        $hours = [];
        for ($h = 0; $h < 24; $h++) {
            $hours[$h] = (int) ($counts[$h] ?? 0);
        }

        return $hours;
    }
}
