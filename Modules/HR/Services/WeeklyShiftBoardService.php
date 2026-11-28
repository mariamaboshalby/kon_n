<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeeWeeklyShift;
use Modules\HR\Models\HRSetting;

class WeeklyShiftBoardService
{
    /** Carbon day index: 0 = Sunday … 6 = Saturday */
    private const CARBON_DAYS = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    private const MAX_MINUTES = 2880;

    /**
     * @return array{day_start_hour: int, day_end_hour: int, week_start_day: string}
     */
    public function settings(): array
    {
        $row = HRSetting::query()->orderBy('id')->first();

        $start = (int) ($row?->shift_day_start_hour ?? 0);
        $end = (int) ($row?->shift_day_end_hour ?? 24);
        $day = (string) ($row?->shift_week_start_day ?? 'saturday');

        if ($start < 0 || $start > 23) {
            $start = 0;
        }
        if ($end <= $start || $end > $start + 24) {
            $end = $start + 24;
        }
        if (! in_array($day, EmployeeWeeklyShift::WEEKDAYS, true)) {
            $day = 'saturday';
        }

        return [
            'day_start_hour' => $start,
            'day_end_hour' => $end,
            'week_start_day' => $day,
        ];
    }

    public function updateSettings(int $startHour, int $endHour, string $weekStartDay): void
    {
        if ($startHour < 0 || $startHour > 23) {
            throw ValidationException::withMessages([
                'day_start_hour' => [__('hr::hr.weekly_shift_invalid_window')],
            ]);
        }
        if ($endHour <= $startHour || $endHour > $startHour + 24) {
            throw ValidationException::withMessages([
                'day_end_hour' => [__('hr::hr.weekly_shift_invalid_window')],
            ]);
        }
        $this->assertWeekday($weekStartDay);

        DB::transaction(function () use ($startHour, $endHour, $weekStartDay): void {
            $row = HRSetting::query()->orderBy('id')->lockForUpdate()->first() ?? new HRSetting;
            $row->fill([
                'shift_day_start_hour' => $startHour,
                'shift_day_end_hour' => $endHour,
                'shift_week_start_day' => $weekStartDay,
            ]);
            $row->save();
        });
    }

    public function weekStartFor(?string $date): CarbonImmutable
    {
        $base = CarbonImmutable::today();
        if ($date !== null && $date !== '') {
            try {
                $base = CarbonImmutable::createFromFormat('Y-m-d', $date)->startOfDay();
            } catch (\Throwable) {
                $base = CarbonImmutable::today();
            }
        }

        $dayIndex = array_search($this->settings()['week_start_day'], self::CARBON_DAYS, true);

        return $base->startOfWeek((int) $dayIndex);
    }

    /**
     * @return array{
     *     settings: array{day_start_hour: int, day_end_hour: int, week_start_day: string},
     *     week_start: string,
     *     week_end: string,
     *     prev_week: string,
     *     next_week: string,
     *     current_week: string,
     *     days: list<array{weekday: string, date: string, label: string}>,
     *     weekdays: list<string>,
     *     employees: list<array{id: int, name: string, department: string}>,
     *     assignments: list<array{id: int, employee_id: int, employee_name: string, weekday: string, shift_date: string, start_time: string, end_time: string}>
     * }
     */
    public function board(?string $date = null): array
    {
        $weekStart = $this->weekStartFor($date);
        $weekEnd = $weekStart->addDays(6);

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $weekStart->addDays($i);
            $weekday = $this->weekdayOf($day);
            $days[] = [
                'weekday' => $weekday,
                'date' => $day->toDateString(),
                'label' => (string) __('hr::hr.'.$weekday),
            ];
        }

        $employees = Employee::query()
            ->with('department:id,title')
            ->whereNull('date_of_fire')
            ->orderBy('name')
            ->get(['id', 'name', 'department_id']);

        $assignments = EmployeeWeeklyShift::query()
            ->with('employee:id,name')
            ->whereBetween('shift_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('shift_date')
            ->orderBy('start_time')
            ->get();

        return [
            'settings' => $this->settings(),
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekEnd->toDateString(),
            'prev_week' => $weekStart->subWeek()->toDateString(),
            'next_week' => $weekStart->addWeek()->toDateString(),
            'current_week' => $this->weekStartFor(null)->toDateString(),
            'days' => $days,
            'weekdays' => array_column($days, 'weekday'),
            'employees' => $employees->map(fn (Employee $employee): array => [
                'id' => (int) $employee->id,
                'name' => (string) $employee->name,
                'department' => (string) ($employee->department?->title ?? ''),
            ])->values()->all(),
            'assignments' => $assignments
                ->filter(fn (EmployeeWeeklyShift $row): bool => $row->employee !== null && $row->start_time !== null && $row->end_time !== null && $row->shift_date !== null)
                ->map(fn (EmployeeWeeklyShift $row): array => [
                    'id' => (int) $row->id,
                    'employee_id' => (int) $row->employee_id,
                    'employee_name' => (string) $row->employee->name,
                    'weekday' => (string) $row->weekday,
                    'shift_date' => substr((string) $row->shift_date, 0, 10),
                    'start_time' => $this->formatTime((string) $row->start_time),
                    'end_time' => $this->formatTime((string) $row->end_time),
                ])->values()->all(),
        ];
    }

    public function assign(int $employeeId, string $shiftDate, string $startTime, string $endTime, ?int $assignmentId = null): void
    {
        $date = $this->parseDate($shiftDate, 'shift_date');
        $weekday = $this->weekdayOf($date);
        $start = $this->toMinutes($startTime, false);
        $end = $this->toMinutes($endTime, true);
        $this->assertRange($start, $end, 'end_time');

        $employee = Employee::query()->whereNull('date_of_fire')->find($employeeId);
        if ($employee === null) {
            throw ValidationException::withMessages([
                'employee_id' => [__('hr::hr.weekly_shift_employee_not_found')],
            ]);
        }

        DB::transaction(function () use ($employee, $date, $weekday, $start, $end, $assignmentId): void {
            $current = null;
            if ($assignmentId !== null) {
                $current = EmployeeWeeklyShift::query()->find($assignmentId);
                if ($current === null) {
                    throw ValidationException::withMessages([
                        'id' => [__('hr::hr.weekly_shift_assignment_not_found')],
                    ]);
                }
            }

            $lockIds = array_values(array_unique(array_filter([
                (int) $employee->id,
                $current !== null ? (int) $current->employee_id : 0,
            ])));
            Employee::query()->whereIn('id', $lockIds)->orderBy('id')->lockForUpdate()->get();

            if ($assignmentId !== null) {
                $current = EmployeeWeeklyShift::query()->lockForUpdate()->find($assignmentId);
                if ($current === null) {
                    throw ValidationException::withMessages([
                        'id' => [__('hr::hr.weekly_shift_assignment_not_found')],
                    ]);
                }
            }

            $others = EmployeeWeeklyShift::query()
                ->where('employee_id', $employee->id)
                ->whereDate('shift_date', $date->toDateString())
                ->when($assignmentId !== null, fn ($query) => $query->where('id', '!=', $assignmentId))
                ->lockForUpdate()
                ->get();

            if ($others->count() >= 2) {
                throw ValidationException::withMessages([
                    'shift_date' => [__('hr::hr.weekly_shift_max_two')],
                ]);
            }

            $payload = [
                'employee_id' => $employee->id,
                'weekday' => $weekday,
                'shift_date' => $date->toDateString(),
                'start_time' => $this->storeTime($start),
                'end_time' => $this->storeTime($end),
                'branch_id' => $employee->branch_id,
            ];

            if ($current !== null) {
                $current->update($payload);

                return;
            }

            EmployeeWeeklyShift::query()->create($payload);
        });
    }

    /**
     * @param  list<array{id?: int|null, employee_id: int, shift_date: string, start_time: string, end_time: string}>  $rows
     */
    public function save(string $weekStartDate, array $rows): void
    {
        $weekStart = $this->weekStartFor($this->parseDate($weekStartDate, 'week_start')->toDateString());
        $weekEnd = $weekStart->addDays(6);

        $prepared = [];
        $counts = [];

        foreach ($rows as $index => $row) {
            $date = $this->parseDate((string) ($row['shift_date'] ?? ''), 'assignments.'.$index.'.shift_date');
            if ($date->lt($weekStart) || $date->gt($weekEnd)) {
                throw ValidationException::withMessages([
                    'assignments.'.$index.'.shift_date' => [__('hr::hr.weekly_shift_date_outside_week')],
                ]);
            }

            $start = $this->toMinutes((string) ($row['start_time'] ?? ''), false);
            $end = $this->toMinutes((string) ($row['end_time'] ?? ''), true);
            $this->assertRange($start, $end, 'assignments.'.$index.'.end_time');

            $employeeId = (int) ($row['employee_id'] ?? 0);
            $bucket = $employeeId.'|'.$date->toDateString();
            $counts[$bucket] = ($counts[$bucket] ?? 0) + 1;
            if ($counts[$bucket] > 2) {
                throw ValidationException::withMessages([
                    'assignments.'.$index.'.shift_date' => [__('hr::hr.weekly_shift_max_two')],
                ]);
            }

            $prepared[] = [
                'id' => isset($row['id']) && (int) $row['id'] > 0 ? (int) $row['id'] : null,
                'employee_id' => $employeeId,
                'weekday' => $this->weekdayOf($date),
                'shift_date' => $date->toDateString(),
                'start' => $start,
                'end' => $end,
            ];
        }

        $employeeIds = array_values(array_unique(array_map(
            fn (array $row): int => $row['employee_id'],
            $prepared
        )));
        $employees = $employeeIds === []
            ? collect()
            : Employee::query()->whereNull('date_of_fire')->whereIn('id', $employeeIds)->get()->keyBy('id');

        foreach ($prepared as $index => $row) {
            if (! $employees->has($row['employee_id'])) {
                throw ValidationException::withMessages([
                    'assignments.'.$index.'.employee_id' => [__('hr::hr.weekly_shift_employee_not_found')],
                ]);
            }
        }

        $range = [$weekStart->toDateString(), $weekEnd->toDateString()];

        DB::transaction(function () use ($prepared, $employees, $employeeIds, $range): void {
            if ($employeeIds !== []) {
                Employee::query()->whereIn('id', $employeeIds)->orderBy('id')->lockForUpdate()->get();
            }

            $existing = EmployeeWeeklyShift::query()
                ->whereBetween('shift_date', $range)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $keep = [];

            foreach ($prepared as $row) {
                $employee = $employees->get($row['employee_id']);
                $payload = [
                    'employee_id' => $row['employee_id'],
                    'weekday' => $row['weekday'],
                    'shift_date' => $row['shift_date'],
                    'start_time' => $this->storeTime($row['start']),
                    'end_time' => $this->storeTime($row['end']),
                    'branch_id' => $employee->branch_id,
                ];

                if ($row['id'] !== null && $existing->has($row['id'])) {
                    $existing->get($row['id'])->update($payload);
                    $keep[] = $row['id'];

                    continue;
                }

                $created = EmployeeWeeklyShift::query()->create($payload);
                $keep[] = (int) $created->id;
            }

            $delete = EmployeeWeeklyShift::query()->whereBetween('shift_date', $range);
            if ($keep !== []) {
                $delete->whereNotIn('id', $keep);
            }
            $delete->delete();
        });
    }

    public function unassign(int $assignmentId): void
    {
        $row = EmployeeWeeklyShift::query()->find($assignmentId);
        if ($row === null) {
            throw ValidationException::withMessages([
                'id' => [__('hr::hr.weekly_shift_assignment_not_found')],
            ]);
        }

        $row->delete();
    }

    private function weekdayOf(CarbonImmutable $date): string
    {
        return strtolower($date->englishDayOfWeek);
    }

    private function parseDate(string $value, string $field): CarbonImmutable
    {
        $raw = substr(trim($value), 0, 10);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            try {
                $date = CarbonImmutable::createFromFormat('Y-m-d', $raw);
                if ($date !== false && $date->toDateString() === $raw) {
                    return $date->startOfDay();
                }
            } catch (\Throwable) {
            }
        }

        throw ValidationException::withMessages([
            $field => [__('hr::hr.weekly_shift_invalid_date')],
        ]);
    }

    private function assertWeekday(string $weekday): void
    {
        if (! in_array($weekday, EmployeeWeeklyShift::WEEKDAYS, true)) {
            throw ValidationException::withMessages([
                'weekday' => [__('hr::hr.weekly_shift_day_not_in_shift')],
            ]);
        }
    }

    private function assertRange(int $start, int $end, string $field): void
    {
        if ($start >= $end || ($end - $start) > 1440) {
            throw ValidationException::withMessages([
                $field => [__('hr::hr.weekly_shift_invalid_range')],
            ]);
        }
    }

    /**
     * Minutes from the start of the shift date; values past 24:00 belong to the next calendar day.
     */
    private function toMinutes(string $value, bool $allowMax): int
    {
        $raw = trim($value);
        if (! preg_match('/^(\d{1,2}):([0-5]\d)/', $raw, $match)) {
            throw ValidationException::withMessages([
                'start_time' => [__('hr::hr.weekly_shift_invalid_range')],
            ]);
        }

        $minutes = ((int) $match[1] * 60) + (int) $match[2];
        $limit = $allowMax ? self::MAX_MINUTES : self::MAX_MINUTES - 1;
        if ($minutes > $limit) {
            throw ValidationException::withMessages([
                'start_time' => [__('hr::hr.weekly_shift_invalid_range')],
            ]);
        }

        return $minutes;
    }

    private function storeTime(int $minutes): string
    {
        return sprintf('%02d:%02d:00', intdiv($minutes, 60), $minutes % 60);
    }

    private function formatTime(string $value): string
    {
        $raw = trim($value);
        if (preg_match('/^(\d{1,3}):(\d{2})/', $raw, $match)) {
            return sprintf('%02d:%s', (int) $match[1], $match[2]);
        }

        return $raw;
    }
}
