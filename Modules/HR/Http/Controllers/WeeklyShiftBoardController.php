<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HR\Models\EmployeeWeeklyShift;
use Modules\HR\Services\WeeklyShiftBoardService;

class WeeklyShiftBoardController extends Controller
{
    private const TIME_RULE = '/^(?:[0-3]\d|4[0-7]):[0-5]\d$/';

    private const END_TIME_RULE = '/^(?:(?:[0-3]\d|4[0-7]):[0-5]\d|48:00)$/';

    public function __construct(
        private readonly WeeklyShiftBoardService $weeklyShiftBoardService
    ) {
        $this->middleware('can:view Shifts')->only(['index', 'data']);
        $this->middleware('can:edit Shifts')->only(['assign', 'unassign', 'save', 'settings']);
    }

    public function index(): View
    {
        $weekDayLabels = [];
        foreach (EmployeeWeeklyShift::WEEKDAYS as $weekday) {
            $weekDayLabels[$weekday] = __('hr::hr.'.$weekday);
        }

        return view('hr::hr-management.shifts.weekly-board', [
            'weekDayLabels' => $weekDayLabels,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'week_start' => 'nullable|date_format:Y-m-d',
        ]);

        return response()->json([
            'data' => $this->weeklyShiftBoardService->board($validated['week_start'] ?? null),
        ]);
    }

    public function assign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer',
            'shift_date' => 'required|date_format:Y-m-d',
            'start_time' => ['required', 'regex:'.self::TIME_RULE],
            'end_time' => ['required', 'regex:'.self::END_TIME_RULE],
            'id' => 'nullable|integer',
        ]);

        $this->weeklyShiftBoardService->assign(
            (int) $validated['employee_id'],
            (string) $validated['shift_date'],
            (string) $validated['start_time'],
            (string) $validated['end_time'],
            isset($validated['id']) ? (int) $validated['id'] : null
        );

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.weekly_shift_assigned'),
            'data' => $this->weeklyShiftBoardService->board((string) $validated['shift_date']),
        ]);
    }

    public function unassign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|integer',
            'week_start' => 'nullable|date_format:Y-m-d',
        ]);

        $this->weeklyShiftBoardService->unassign((int) $validated['id']);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.weekly_shift_removed'),
            'data' => $this->weeklyShiftBoardService->board($validated['week_start'] ?? null),
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'week_start' => 'required|date_format:Y-m-d',
            'assignments' => 'present|array',
            'assignments.*.id' => 'nullable|integer',
            'assignments.*.employee_id' => 'required|integer',
            'assignments.*.shift_date' => 'required|date_format:Y-m-d',
            'assignments.*.start_time' => ['required', 'regex:'.self::TIME_RULE],
            'assignments.*.end_time' => ['required', 'regex:'.self::END_TIME_RULE],
        ]);

        $this->weeklyShiftBoardService->save((string) $validated['week_start'], $validated['assignments']);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.weekly_shift_saved'),
            'data' => $this->weeklyShiftBoardService->board((string) $validated['week_start']),
        ]);
    }

    public function settings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'day_start_hour' => 'required|integer|min:0|max:23',
            'day_end_hour' => 'required|integer|min:1|max:47',
            'week_start_day' => 'required|string|in:'.implode(',', EmployeeWeeklyShift::WEEKDAYS),
            'week_start' => 'nullable|date_format:Y-m-d',
        ]);

        $this->weeklyShiftBoardService->updateSettings(
            (int) $validated['day_start_hour'],
            (int) $validated['day_end_hour'],
            (string) $validated['week_start_day']
        );

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.weekly_shift_settings_saved'),
            'data' => $this->weeklyShiftBoardService->board($validated['week_start'] ?? null),
        ]);
    }
}
