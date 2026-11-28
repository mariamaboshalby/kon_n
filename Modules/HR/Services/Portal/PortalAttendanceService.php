<?php

declare(strict_types=1);

namespace Modules\HR\Services\Portal;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\HR\Models\Attendance;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeeDevice;
use Modules\HR\Models\LeaveRequest;
use Modules\Progress\Models\ProjectProgress;

final class PortalAttendanceService
{
    private const DAY_MAP = [
        0 => 'sunday',
        1 => 'monday',
        2 => 'tuesday',
        3 => 'wednesday',
        4 => 'thursday',
        5 => 'friday',
        6 => 'saturday',
    ];

    public function __construct(private readonly GeofenceService $geofence)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function today(Employee $employee): array
    {
        $now = $this->now();
        $last = $this->lastWithinWindow($employee, $now);
        $todayRecords = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $now->toDateString())
            ->orderBy('time')
            ->get(['id', 'type', 'time', 'status', 'is_remote', 'is_suspicious']);

        $target = null;
        $projects = [];
        if ($employee->location_type === 'project') {
            $projects = $this->geofence->projectsWithLocation($employee);
        } else {
            $target = $this->geofence->companyLocation();
        }

        return [
            'server_time' => $now->toIso8601String(),
            'date' => $now->toDateString(),
            'next_action' => ($last && $last->type === 'check_in') ? 'check_out' : 'check_in',
            'last' => $last ? $this->serialize($last) : null,
            'records' => $todayRecords->map(fn (Attendance $a) => $this->serialize($a))->values(),
            'is_working_day' => $this->isWorkingDay($employee, $now),
            'location' => [
                'required' => (bool) $employee->require_location,
                'type' => $employee->location_type ?: 'company',
                'allow_remote' => (bool) $employee->allow_remote_work,
                'target' => $target ? [
                    'latitude' => $target['latitude'],
                    'longitude' => $target['longitude'],
                    'radius' => $target['radius'],
                    'label' => $target['label'],
                ] : null,
                'projects' => $projects,
            ],
        ];
    }

    /**
     * @param  array{type?: string|null, latitude?: float|null, longitude?: float|null, accuracy?: float|null, is_mock?: bool|null, project_id?: int|null, notes?: string|null, client_request_id: string}  $data
     * @return array{attendance: Attendance, duplicate: bool}
     */
    public function record(Employee $employee, ?EmployeeDevice $device, array $data, ?UploadedFile $selfie): array
    {
        $settings = PortalSettings::current();
        $clientRequestId = (string) $data['client_request_id'];

        $existing = $this->findByClientRequest($employee, $clientRequestId);
        if ($existing) {
            return ['attendance' => $existing, 'duplicate' => true];
        }

        if ($settings->selfieRequired() && ! $selfie) {
            throw new PortalException(__('hr::portal.selfie_required'), 'selfie_required');
        }

        $evaluation = $this->evaluateLocation($employee, $device, $data, $settings);

        try {
            $attendance = DB::transaction(function () use ($employee, $device, $data, $selfie, $evaluation, $clientRequestId): Attendance {
                Employee::query()->whereKey($employee->id)->lockForUpdate()->first(['id']);

                $now = $this->now();
                $type = $this->assertSequence($employee, $data['type'] ?? null, $now);

                $projectCode = null;
                if (! empty($data['project_id'])) {
                    // قبول مشاريع وحدة المشاريع (is_progress=0) ومشاريع لوحة Progress معًا
                    $projectCode = ProjectProgress::query()
                        ->withoutGlobalScope('progressOnly')
                        ->whereKey((int) $data['project_id'])
                        ->value('project_code');
                }

                $selfiePath = $selfie
                    ? Storage::disk('local')->putFile('hr/portal/selfies/'.$now->format('Y/m'), $selfie)
                    : null;

                return Attendance::create([
                    'employee_id' => $employee->id,
                    'employee_attendance_finger_print_id' => $employee->finger_print_id,
                    'employee_attendance_finger_print_name' => $employee->finger_print_name,
                    'type' => $type,
                    'date' => $this->workdayDateFor($employee, $now, $type),
                    'time' => $now->format('H:i:s'),
                    'location' => $evaluation['location'],
                    'status' => 'pending',
                    'notes' => isset($data['notes']) ? mb_substr((string) $data['notes'], 0, 250) : null,
                    'project_code' => $projectCode,
                    'device_type' => 'portal_'.($device?->platform ?: 'app'),
                    'user_id' => null,
                    'employee_device_id' => $device?->id,
                    'distance_meters' => $evaluation['distance'],
                    'gps_accuracy' => $evaluation['accuracy'],
                    'is_remote' => $evaluation['is_remote'],
                    'is_suspicious' => $evaluation['suspicious'] !== [],
                    'suspicious_reason' => $evaluation['suspicious'] !== [] ? implode(',', $evaluation['suspicious']) : null,
                    'client_request_id' => $clientRequestId,
                    'selfie_path' => $selfiePath ?: null,
                ]);
            });
        } catch (QueryException $e) {
            $duplicate = $this->findByClientRequest($employee, $clientRequestId);
            if ($duplicate) {
                return ['attendance' => $duplicate, 'duplicate' => true];
            }

            throw $e;
        }

        $device?->forceFill(['last_seen_at' => now()])->save();

        return ['attendance' => $attendance, 'duplicate' => false];
    }

    /**
     * @return array<string, mixed>
     */
    public function history(Employee $employee, string $month, int $page): array
    {
        try {
            $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable) {
            $start = $this->now()->startOfMonth();
        }
        $end = $start->copy()->endOfMonth();

        $paginator = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->simplePaginate(30, ['*'], 'page', max(1, $page));

        return [
            'month' => $start->format('Y-m'),
            'summary' => $page <= 1 ? $this->monthSummary($employee, $start, $end) : null,
            'data' => collect($paginator->items())->map(fn (Attendance $a) => $this->serialize($a))->values(),
            'has_more' => $paginator->hasMorePages(),
            'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(Attendance $attendance): array
    {
        return [
            'id' => (int) $attendance->id,
            'type' => $attendance->type,
            'date' => $attendance->date?->format('Y-m-d'),
            'time' => substr((string) $attendance->time, 0, 5),
            'status' => $attendance->status,
            'is_remote' => (bool) ($attendance->is_remote ?? false),
            'is_suspicious' => (bool) ($attendance->is_suspicious ?? false),
            'project_code' => $attendance->project_code ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{location: array<string, mixed>|null, distance: int|null, accuracy: float|null, is_remote: bool, suspicious: list<string>}
     */
    private function evaluateLocation(Employee $employee, ?EmployeeDevice $device, array $data, PortalSettings $settings): array
    {
        $lat = isset($data['latitude']) ? (float) $data['latitude'] : null;
        $lng = isset($data['longitude']) ? (float) $data['longitude'] : null;
        $accuracy = isset($data['accuracy']) ? round((float) $data['accuracy'], 2) : null;
        $isMock = (bool) ($data['is_mock'] ?? false);
        $enforce = (bool) $employee->require_location && $settings->enforceGeofence();
        $suspicious = [];

        if ($device && ! $device->isApproved()) {
            $suspicious[] = 'unapproved_device';
        }

        if ($isMock) {
            if ($settings->blockMockLocation()) {
                throw new PortalException(__('hr::portal.mock_location_detected'), 'mock_location', 422);
            }
            $suspicious[] = 'mock_location';
        }

        if ($lat === null || $lng === null) {
            if ($enforce) {
                throw new PortalException(__('hr::portal.location_required'), 'location_required', 422);
            }

            return ['location' => null, 'distance' => null, 'accuracy' => null, 'is_remote' => false, 'suspicious' => $suspicious];
        }

        if ($accuracy !== null && $accuracy > $settings->maxGpsAccuracy()) {
            if ($enforce) {
                throw new PortalException(
                    __('hr::portal.gps_accuracy_low', ['accuracy' => (int) $accuracy, 'max' => $settings->maxGpsAccuracy()]),
                    'gps_accuracy_low',
                    422
                );
            }
            $suspicious[] = 'low_accuracy';
        }

        $projectId = ! empty($data['project_id']) ? (int) $data['project_id'] : null;
        if ($enforce && $employee->location_type === 'project' && ! $projectId) {
            throw new PortalException(__('hr::portal.project_required'), 'project_required', 422);
        }

        $target = $this->geofence->resolveTarget($employee, $projectId);
        $distance = null;
        $isRemote = false;

        if ($target === null) {
            if ($enforce) {
                throw new PortalException(__('hr::portal.target_location_not_configured'), 'target_not_configured', 422);
            }
        } else {
            $distance = (int) round($this->geofence->distanceMeters($lat, $lng, $target['latitude'], $target['longitude']));

            if ($distance > $target['radius']) {
                if ($employee->allow_remote_work) {
                    $isRemote = true;
                } elseif ($enforce) {
                    throw new PortalException(
                        __('hr::portal.outside_geofence', [
                            'distance' => $distance,
                            'label' => $target['label'],
                            'radius' => $target['radius'],
                        ]),
                        'outside_geofence',
                        422,
                        ['distance' => $distance, 'radius' => $target['radius']]
                    );
                } else {
                    $suspicious[] = 'outside_geofence';
                }
            }
        }

        return [
            'location' => [
                'latitude' => round($lat, 6),
                'longitude' => round($lng, 6),
                'accuracy' => $accuracy,
                'is_mock' => $isMock,
                'captured_at' => now()->toISOString(),
                'source' => 'portal_app',
            ],
            'distance' => $distance,
            'accuracy' => $accuracy,
            'is_remote' => $isRemote,
            'suspicious' => $suspicious,
        ];
    }

    /**
     * تاريخ يوم العمل لبصمة تُسجَّل الآن.
     *
     * قاعدة الانصراف بعد منتصف الليل: لو البصمة check_out ووقتها أصغر من وقت
     * آخر check_in (يعني عدّت منتصف الليل) وكان الـ check_in في يوم تقويمي
     * مختلف، تتسجل البصمة بتاريخ يوم الشغل (تاريخ الـ check_in) مش تاريخ اليوم
     * اللي بعده، فتتقرن صح في التقارير والمعالجة.
     */
    public function workdayDateFor(Employee $employee, Carbon $now, string $type): string
    {
        if ($type !== 'check_out') {
            return $now->toDateString();
        }

        $last = $this->lastWithinWindow($employee, $now);

        if ($last && $last->type === 'check_in') {
            $lastAt = $this->recordedAt($last);
            $lastTime = substr((string) $last->time, 0, 8);

            if ($lastAt->toDateString() !== $now->toDateString() && $now->format('H:i:s') < $lastTime) {
                return $lastAt->toDateString();
            }
        }

        return $now->toDateString();
    }

    /**
     * يتحقق من تسلسل الحضور/الانصراف والحد الأدنى بين الحركتين ويُرجع النوع الصحيح.
     * يجب استدعاؤه داخل transaction بعد lockForUpdate على صف الموظف.
     *
     * @throws PortalException
     */
    public function assertSequence(Employee $employee, ?string $requestedType, Carbon $now): string
    {
        $last = $this->lastWithinWindow($employee, $now);
        $type = $this->resolveType($requestedType, $last);

        $minInterval = (int) config('hr.portal.attendance_min_interval_seconds', 60);
        if ($last && $this->recordedAt($last)->diffInSeconds($now, true) < $minInterval) {
            throw new PortalException(__('hr::portal.attendance_too_soon'), 'too_soon', 409);
        }

        return $type;
    }

    private function resolveType(?string $requested, ?Attendance $last): string
    {
        $expected = ($last && $last->type === 'check_in') ? 'check_out' : 'check_in';

        if ($requested === null || $requested === '') {
            return $expected;
        }

        if ($requested !== $expected) {
            throw new PortalException(
                $requested === 'check_in'
                    ? __('hr::portal.must_check_out_first')
                    : __('hr::portal.must_check_in_first'),
                'invalid_sequence',
                409,
                ['expected' => $expected]
            );
        }

        return $requested;
    }

    /**
     * آخر حركة خلال نافذة (افتراضي 18 ساعة) — تدعم الورديات التي تعبر منتصف الليل.
     */
    private function lastWithinWindow(Employee $employee, Carbon $now): ?Attendance
    {
        $hours = (int) config('hr.portal.sequence_window_hours', 18);
        $from = $now->copy()->subHours($hours);

        $candidates = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$from->toDateString(), $now->toDateString()])
            // الترتيب بـ id (زمن الإدراج) لأن انصراف ما بعد منتصف الليل يتسجل
            // بتاريخ يوم الشغل (اليوم السابق) فترتيب التاريخ/الوقت لا يعكس التسلسل.
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        foreach ($candidates as $candidate) {
            if ($this->recordedAt($candidate)->gte($from)) {
                return $candidate;
            }
        }

        return null;
    }

    private function recordedAt(Attendance $attendance): Carbon
    {
        return Carbon::parse(
            $attendance->date->format('Y-m-d').' '.substr((string) $attendance->time, 0, 8),
            config('app.timezone')
        );
    }

    private function findByClientRequest(Employee $employee, string $clientRequestId): ?Attendance
    {
        return Attendance::query()
            ->where('employee_id', $employee->id)
            ->where('client_request_id', $clientRequestId)
            ->first();
    }

    private function isWorkingDay(Employee $employee, Carbon $date): bool
    {
        $days = $this->shiftDays($employee);

        return $days === [] || in_array(self::DAY_MAP[$date->dayOfWeek], $days, true);
    }

    /**
     * @return list<string>
     */
    private function shiftDays(Employee $employee): array
    {
        $employee->loadMissing('shift');
        if (! $employee->shift) {
            return [];
        }

        $days = json_decode((string) $employee->shift->days, true);

        return is_array($days) ? array_values(array_map('strval', $days)) : [];
    }

    /**
     * @return array<string, int>
     */
    private function monthSummary(Employee $employee, Carbon $start, Carbon $end): array
    {
        $today = $this->now()->startOfDay();
        $lastDay = $end->lt($today) ? $end->copy() : $today->copy();

        $checkIns = Attendance::query()
            ->where('employee_id', $employee->id)
            ->where('type', 'check_in')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('time')
            ->get(['date', 'time'])
            ->groupBy(fn (Attendance $a) => $a->date->format('Y-m-d'));

        $presentDays = $checkIns->count();

        $lateDays = 0;
        $employee->loadMissing('shift');
        $shift = $employee->shift;
        if ($shift && $shift->start_time) {
            $grace = (int) ($shift->allowed_late_minutes ?? 0);
            foreach ($checkIns as $date => $records) {
                $first = $records->first();
                $limit = Carbon::parse($date.' '.substr((string) $shift->start_time, 0, 8))->addMinutes($grace);
                if (Carbon::parse($date.' '.substr((string) $first->time, 0, 8))->gt($limit)) {
                    $lateDays++;
                }
            }
        }

        $leaveDates = [];
        LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->get(['start_date', 'end_date'])
            ->each(function (LeaveRequest $leave) use (&$leaveDates, $start, $end): void {
                $cursor = $leave->start_date->copy()->max($start);
                $stop = $leave->end_date->copy()->min($end);
                while ($cursor->lte($stop)) {
                    $leaveDates[$cursor->toDateString()] = true;
                    $cursor->addDay();
                }
            });

        $workingDays = 0;
        $absentDays = 0;
        if ($lastDay->gte($start)) {
            $cursor = $start->copy();
            while ($cursor->lte($lastDay)) {
                $key = $cursor->toDateString();
                if ($this->isWorkingDay($employee, $cursor)) {
                    $workingDays++;
                    if (! $checkIns->has($key) && ! isset($leaveDates[$key]) && $cursor->lt($today)) {
                        $absentDays++;
                    }
                }
                $cursor->addDay();
            }
        }

        return [
            'present_days' => $presentDays,
            'late_days' => $lateDays,
            'absent_days' => $absentDays,
            'leave_days' => count($leaveDates),
            'working_days' => $workingDays,
        ];
    }

    private function now(): Carbon
    {
        return Carbon::now(config('app.timezone'));
    }
}
