<?php

declare(strict_types=1);

namespace Modules\HR\Services\Portal;

use Carbon\Carbon;
use Modules\HR\Models\Attendance;
use Modules\HR\Models\AttendanceProcessing;
use Modules\HR\Models\Covenant;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeeAdvance;
use Modules\HR\Models\EmployeeDeductionReward;
use Modules\HR\Models\EmployeeLeaveBalance;
use Modules\HR\Models\EmployeePortalNotification;
use Modules\HR\Models\LeaveRequest;
use Modules\HR\Models\LeaveType;

/**
 * بيانات الخدمة الذاتية (قراءة فقط) لموظف البوابة.
 */
final class PortalSelfService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function leaveBalances(Employee $employee): array
    {
        return EmployeeLeaveBalance::query()
            ->with('leaveType:id,name,is_paid')
            ->where('employee_id', $employee->id)
            ->where('year', now()->year)
            ->get()
            ->map(fn (EmployeeLeaveBalance $b) => [
                'leave_type_id' => (int) $b->leave_type_id,
                'leave_type' => $b->leaveType?->name,
                'is_paid' => (bool) $b->leaveType?->is_paid,
                'opening' => (float) $b->opening_balance_days,
                'used' => (float) $b->used_days,
                'pending' => (float) $b->pending_days,
                'remaining' => (float) $b->remaining_days,
            ])->values()->all();
    }

    /**
     * @return list<array{id: int, name: string, is_paid: bool, max_per_request_days: int|null}>
     */
    public function leaveTypes(): array
    {
        return LeaveType::query()->orderBy('name')->get(['id', 'name', 'is_paid', 'max_per_request_days'])
            ->map(fn (LeaveType $t) => [
                'id' => (int) $t->id,
                'name' => (string) $t->name,
                'is_paid' => (bool) $t->is_paid,
                'max_per_request_days' => $t->max_per_request_days !== null ? (int) $t->max_per_request_days : null,
            ])->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function finance(Employee $employee, int $year): array
    {
        $items = EmployeeDeductionReward::query()
            ->where('employee_id', $employee->id)
            ->whereYear('date', $year)
            ->orderByDesc('date')
            ->limit(200)
            ->get(['id', 'type', 'reason', 'amount', 'date']);

        $advances = EmployeeAdvance::query()
            ->where('employee_id', $employee->id)
            ->whereYear('date', $year)
            ->orderByDesc('date')
            ->limit(200)
            ->get(['id', 'amount', 'date', 'reason', 'status', 'deducted_from_salary']);

        return [
            'year' => $year,
            'totals' => [
                'deductions' => (float) $items->where('type', 'deduction')->sum('amount'),
                'rewards' => (float) $items->where('type', 'reward')->sum('amount'),
                'advances' => (float) $advances->where('status', 'approved')->sum('amount'),
            ],
            'deductions_rewards' => $items->map(fn ($i) => [
                'id' => (int) $i->id,
                'type' => $i->type,
                'type_label' => __('hr::portal.finance_'.$i->type),
                'reason' => $i->reason,
                'amount' => (float) $i->amount,
                'date' => Carbon::parse($i->date)->format('Y-m-d'),
            ])->values(),
            'advances' => $advances->map(fn (EmployeeAdvance $a) => [
                'id' => (int) $a->id,
                'amount' => (float) $a->amount,
                'date' => $a->date?->format('Y-m-d'),
                'reason' => $a->reason,
                'status' => $a->status,
                'status_label' => __('hr::portal.status_'.$a->status),
                'deducted' => (bool) $a->deducted_from_salary,
            ])->values(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function payslips(Employee $employee): array
    {
        return AttendanceProcessing::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->orderByDesc('period_start')
            ->limit(24)
            ->get()
            ->map(fn (AttendanceProcessing $p) => [
                'id' => (int) $p->id,
                'period_start' => $p->period_start?->format('Y-m-d'),
                'period_end' => $p->period_end?->format('Y-m-d'),
                'working_days' => (int) $p->working_days,
                'actual_work_days' => (int) $p->actual_work_days,
                'absent_days' => (int) $p->absent_days,
                'overtime_work_days' => (int) $p->overtime_work_days,
                'total_late_minutes' => (int) $p->total_late_minutes,
                'unpaid_leave_days' => (float) $p->unpaid_leave_days,
                'salary_due' => (float) $p->salary_due,
                'total_salary' => (float) $p->total_salary,
            ])->values()->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function covenants(Employee $employee): array
    {
        return Covenant::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('id')
            ->get(['id', 'name', 'description', 'created_at'])
            ->map(fn (Covenant $c) => [
                'id' => (int) $c->id,
                'name' => (string) $c->name,
                'description' => $c->description,
                'date' => $c->created_at?->format('Y-m-d'),
            ])->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function teamToday(Employee $manager): array
    {
        $today = Carbon::now(config('app.timezone'))->toDateString();
        $members = Employee::query()
            ->with('shift')
            ->where('line_manager_id', $manager->id)
            ->whereNull('date_of_fire')
            ->orderBy('name')
            ->get(['id', 'name', 'shift_id']);

        if ($members->isEmpty()) {
            return ['counts' => ['present' => 0, 'late' => 0, 'absent' => 0, 'on_leave' => 0], 'members' => []];
        }

        $ids = $members->pluck('id')->all();

        $firstCheckIns = Attendance::query()
            ->whereIn('employee_id', $ids)
            ->whereDate('date', $today)
            ->where('type', 'check_in')
            ->orderBy('time')
            ->get(['employee_id', 'time'])
            ->groupBy('employee_id')
            ->map(fn ($rows) => $rows->first());

        $onLeave = LeaveRequest::query()
            ->whereIn('employee_id', $ids)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->pluck('employee_id')
            ->flip();

        $counts = ['present' => 0, 'late' => 0, 'absent' => 0, 'on_leave' => 0];
        $rows = [];

        foreach ($members as $member) {
            $checkIn = $firstCheckIns->get($member->id);
            $state = 'absent';

            if ($onLeave->has($member->id)) {
                $state = 'on_leave';
            } elseif ($checkIn) {
                $state = 'present';
                $shift = $member->shift;
                if ($shift && $shift->start_time) {
                    $limit = Carbon::parse($today.' '.substr((string) $shift->start_time, 0, 8))
                        ->addMinutes((int) ($shift->allowed_late_minutes ?? 0));
                    if (Carbon::parse($today.' '.substr((string) $checkIn->time, 0, 8))->gt($limit)) {
                        $state = 'late';
                    }
                }
            }

            $counts[$state]++;
            $rows[] = [
                'id' => (int) $member->id,
                'name' => (string) $member->name,
                'state' => $state,
                'state_label' => __('hr::portal.team_'.$state),
                'check_in' => $checkIn ? substr((string) $checkIn->time, 0, 5) : null,
            ];
        }

        return ['counts' => $counts, 'members' => $rows];
    }

    /**
     * @return array{data: list<array<string, mixed>>, unread: int, has_more: bool, next_page: int|null}
     */
    public function notifications(Employee $employee, int $page): array
    {
        $paginator = EmployeePortalNotification::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('id')
            ->simplePaginate(25, ['*'], 'page', max(1, $page));

        return [
            'data' => collect($paginator->items())->map(fn (EmployeePortalNotification $n) => [
                'id' => (int) $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'body' => $n->body,
                'data' => $n->data ?? [],
                'read' => $n->read_at !== null,
                'at' => $n->created_at?->format('Y-m-d H:i'),
            ])->values()->all(),
            'unread' => $this->unreadCount($employee),
            'has_more' => $paginator->hasMorePages(),
            'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
        ];
    }

    public function unreadCount(Employee $employee): int
    {
        return EmployeePortalNotification::query()
            ->where('employee_id', $employee->id)
            ->whereNull('read_at')
            ->count();
    }

    public function markRead(Employee $employee, ?int $id): void
    {
        EmployeePortalNotification::query()
            ->where('employee_id', $employee->id)
            ->whereNull('read_at')
            ->when($id, fn ($q) => $q->whereKey($id))
            ->update(['read_at' => now()]);
    }
}
