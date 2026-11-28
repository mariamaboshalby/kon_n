<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\HR\Events\LeaveRequestApproved;
use Modules\HR\Events\LeaveRequestCancelled;
use Modules\HR\Events\LeaveRequestRejected;
use Modules\HR\Events\LeaveRequestSubmitted;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeeLeaveBalance;
use Modules\HR\Models\HRSetting;
use Modules\HR\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestService
{
    public function __construct(
        private readonly LeaveBalanceService $leaveBalanceService
    ) {
    }

    /**
     * @param  array{search?: string|null, employee_id?: string|int|null, leave_type_id?: string|int|null, status?: string|null, date_from?: string|null, date_to?: string|null}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function list(array $filters = [], bool $trashed = false, ?User $user = null): Collection
    {
        $query = $trashed
            ? LeaveRequest::onlyTrashed()
            : LeaveRequest::query();

        $search = trim((string) ($filters['search'] ?? ''));
        $employeeId = $filters['employee_id'] ?? null;
        $leaveTypeId = $filters['leave_type_id'] ?? null;
        $status = $filters['status'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        $query->with(['employee:id,name', 'leaveType:id,name', 'approver:id,name'])
            ->when($search !== '', function ($q) use ($search) {
                $q->whereHas('employee', function ($employeeQuery) use ($search) {
                    $employeeQuery->where('name', 'like', '%'.$search.'%');
                });
            })
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->when($leaveTypeId, fn ($q) => $q->where('leave_type_id', $leaveTypeId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($dateFrom, fn ($q) => $q->where('start_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('end_date', '<=', $dateTo));

        if ($trashed) {
            $query->orderByDesc('deleted_at');
        } else {
            $query->orderByDesc('created_at');
        }

        $user ??= auth()->user();

        return $query->get()
            ->map(fn (LeaveRequest $leaveRequest) => $this->serialize($leaveRequest, $user, $trashed))
            ->values();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): LeaveRequest
    {
        $calculatedDays = $this->calculateDays((string) $data['start_date'], (string) $data['end_date']);
        $overlapsAttendance = $this->hasAttendanceOverlap(
            (int) $data['employee_id'],
            (string) $data['start_date'],
            (string) $data['end_date']
        );

        $this->assertCanSave($data, $calculatedDays, null);

        return LeaveRequest::create([
            'employee_id' => $data['employee_id'],
            'leave_type_id' => $data['leave_type_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'duration_days' => $calculatedDays,
            'status' => 'draft',
            'reason' => $data['reason'] ?? null,
            'overlaps_attendance' => $overlapsAttendance,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(LeaveRequest $leaveRequest, array $data): LeaveRequest
    {
        $calculatedDays = $this->calculateDays((string) $data['start_date'], (string) $data['end_date']);
        $overlapsAttendance = $this->hasAttendanceOverlap(
            (int) $data['employee_id'],
            (string) $data['start_date'],
            (string) $data['end_date']
        );

        $this->assertCanSave($data, $calculatedDays, $leaveRequest);

        $leaveRequest->update([
            'employee_id' => $data['employee_id'],
            'leave_type_id' => $data['leave_type_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'duration_days' => $calculatedDays,
            'reason' => $data['reason'] ?? null,
            'overlaps_attendance' => $overlapsAttendance,
        ]);

        return $leaveRequest->fresh(['employee', 'leaveType', 'approver']) ?? $leaveRequest;
    }

    public function delete(LeaveRequest $leaveRequest): void
    {
        $leaveRequest->delete();
    }

    public function restore(LeaveRequest $leaveRequest): void
    {
        if (! $leaveRequest->trashed()) {
            throw ValidationException::withMessages([
                'general' => [__('hr::hr.leave_request_not_trashed')],
            ]);
        }

        $leaveRequest->restore();
    }

    public function forceDelete(LeaveRequest $leaveRequest): void
    {
        $leaveRequest->forceDelete();
    }

    public function submit(LeaveRequest $leaveRequest): void
    {
        if (! $leaveRequest->isDraft()) {
            throw ValidationException::withMessages([
                'general' => [__('hr::hr.cannot_submit_leave_request')],
            ]);
        }

        DB::transaction(function () use ($leaveRequest) {
            $leaveRequest->update(['status' => 'submitted']);
            $leaveRequest->refresh()->load(['employee', 'leaveType']);
            event(new LeaveRequestSubmitted($leaveRequest));
        });
    }

    public function approve(LeaveRequest $leaveRequest, ?int $approverId): void
    {
        if (! $leaveRequest->canBeApproved()) {
            throw ValidationException::withMessages([
                'general' => [$leaveRequest->approval_error ?? __('hr::hr.cannot_approve_leave_request')],
            ]);
        }

        DB::transaction(function () use ($leaveRequest, $approverId) {
            $leaveRequest->update([
                'status' => 'approved',
                'approver_id' => $approverId,
                'approved_at' => now(),
            ]);
            $leaveRequest->refresh()->load(['employee', 'leaveType']);
            event(new LeaveRequestApproved($leaveRequest));
        });
    }

    public function reject(LeaveRequest $leaveRequest, ?int $approverId): void
    {
        if (! $leaveRequest->canBeRejected()) {
            throw ValidationException::withMessages([
                'general' => [__('hr::hr.cannot_reject_leave_request')],
            ]);
        }

        DB::transaction(function () use ($leaveRequest, $approverId) {
            $leaveRequest->update([
                'status' => 'rejected',
                'approver_id' => $approverId,
            ]);
            $leaveRequest->refresh()->load(['employee', 'leaveType']);
            event(new LeaveRequestRejected($leaveRequest));
        });
    }

    public function cancel(LeaveRequest $leaveRequest): void
    {
        if (! $leaveRequest->canBeCancelled()) {
            throw ValidationException::withMessages([
                'general' => [__('hr::hr.cannot_cancel_leave_request')],
            ]);
        }

        DB::transaction(function () use ($leaveRequest) {
            $leaveRequest->update(['status' => 'cancelled']);
            $leaveRequest->refresh()->load(['employee', 'leaveType']);
            event(new LeaveRequestCancelled($leaveRequest));
        });
    }

    /**
     * @param  array{employee_id?: string|int|null, leave_type_id?: string|int|null, start_date?: string|null, end_date?: string|null}  $input
     * @return array<string, mixed>
     */
    public function preview(array $input): array
    {
        $employeeId = (int) ($input['employee_id'] ?? 0);
        $leaveTypeId = (int) ($input['leave_type_id'] ?? 0);
        $startDate = (string) ($input['start_date'] ?? '');
        $endDate = (string) ($input['end_date'] ?? '');

        $calculatedDays = ($startDate !== '' && $endDate !== '')
            ? $this->calculateDays($startDate, $endDate)
            : 0.0;

        $availableBalance = 0.0;
        $hasBalance = false;
        $isPaid = false;

        if ($employeeId && $leaveTypeId) {
            $balance = EmployeeLeaveBalance::query()
                ->with('leaveType')
                ->where('employee_id', $employeeId)
                ->where('leave_type_id', $leaveTypeId)
                ->where('year', now()->year)
                ->first();

            if ($balance) {
                $hasBalance = true;
                $availableBalance = (float) $balance->remaining_days;
                $isPaid = (bool) ($balance->leaveType?->is_paid);
            }
        }

        $overlapsAttendance = false;
        if ($employeeId && $startDate !== '' && $endDate !== '') {
            $overlapsAttendance = $this->hasAttendanceOverlap($employeeId, $startDate, $endDate);
        }

        return [
            'calculated_days' => $calculatedDays,
            'available_balance' => $availableBalance,
            'remaining_after' => $availableBalance - $calculatedDays,
            'has_balance' => $hasBalance,
            'is_paid' => $isPaid,
            'overlaps_attendance' => $overlapsAttendance,
        ];
    }

    public function statusBadgeClass(string $status): string
    {
        return match ($status) {
            'draft' => 'bg-secondary',
            'submitted' => 'bg-warning',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            'cancelled' => 'bg-dark',
            default => 'bg-secondary',
        };
    }

    public function statusText(string $status): string
    {
        return match ($status) {
            'draft' => __('hr::hr.draft'),
            'submitted' => __('hr::hr.submitted'),
            'approved' => __('hr::hr.approved'),
            'rejected' => __('hr::hr.rejected'),
            'cancelled' => __('hr::hr.cancelled'),
            default => __('hr::hr.status_unknown'),
        };
    }

    public function statusDescription(string $status): string
    {
        return match ($status) {
            'draft' => __('hr::hr.status_draft_description'),
            'submitted' => __('hr::hr.status_submitted_description'),
            'approved' => __('hr::hr.status_approved_description'),
            'rejected' => __('hr::hr.status_rejected_description'),
            'cancelled' => __('hr::hr.status_cancelled_description'),
            default => __('hr::hr.status_unknown'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(LeaveRequest $leaveRequest, ?User $user = null, bool $trashed = false): array
    {
        $user ??= auth()->user();
        $canEditRequests = $user?->can('edit Leave Requests') ?? false;
        $canDeleteRequests = $user?->can('delete Leave Requests') ?? false;

        return [
            'id' => $leaveRequest->id,
            'employee_name' => $leaveRequest->employee?->name ?? '',
            'leave_type_name' => $leaveRequest->leaveType?->name ?? '',
            'start_date' => $leaveRequest->start_date?->format('Y-m-d'),
            'end_date' => $leaveRequest->end_date?->format('Y-m-d'),
            'duration_days' => (float) $leaveRequest->duration_days,
            'status' => $leaveRequest->status,
            'status_text' => $this->statusText((string) $leaveRequest->status),
            'status_badge' => $this->statusBadgeClass((string) $leaveRequest->status),
            'approver_name' => $leaveRequest->approver?->name,
            'approved_at' => $leaveRequest->approved_at?->format('Y-m-d H:i'),
            'updated_at' => $leaveRequest->updated_at?->format('Y-m-d H:i'),
            'created_at' => $leaveRequest->created_at?->format('Y-m-d H:i'),
            'deleted_at' => $leaveRequest->deleted_at?->format('Y-m-d H:i'),
            'can_approve' => $leaveRequest->isSubmitted() && (
                ($user?->can('edit Leave Approvals') ?? false) || $canEditRequests
            ),
            'can_reject' => $leaveRequest->isSubmitted() && (
                ($user?->can('edit Leave Rejections') ?? false)
                || ($user?->can('view Leave Rejections') ?? false)
                || $canEditRequests
            ),
            'can_cancel' => $user ? $user->can('cancel', $leaveRequest) : false,
            'can_edit' => $canEditRequests,
            'can_delete' => $canDeleteRequests,
            'can_restore' => $trashed && $canDeleteRequests,
            'can_force_delete' => $trashed && $canDeleteRequests,
            'show_url' => route('hr.leaves.requests.show', $leaveRequest->id),
            'edit_url' => route('hr.leaves.requests.edit', $leaveRequest->id),
        ];
    }

    public function calculateDays(string $startDate, string $endDate): float
    {
        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            if ($end->lt($start)) {
                return 0.0;
            }

            return (float) $start->diffInDays($end);
        } catch (\Throwable) {
            return 0.0;
        }
    }

    public function hasAttendanceOverlap(int $employeeId, string $startDate, string $endDate): bool
    {
        $employee = Employee::query()->find($employeeId);
        if (! $employee) {
            return false;
        }

        return $employee->attendances()
            ->whereBetween('date', [$startDate, $endDate])
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertCanSave(array $data, float $calculatedDays, ?LeaveRequest $existing): void
    {
        $employeeId = (int) $data['employee_id'];
        $leaveTypeId = (int) $data['leave_type_id'];
        $startDate = (string) $data['start_date'];
        $endDate = (string) $data['end_date'];
        $excludeId = $existing?->id;

        if ($this->leaveBalanceService->hasOverlappingApprovedRequests($employeeId, $startDate, $endDate, $excludeId)) {
            throw ValidationException::withMessages([
                'general' => [__('hr::hr.overlapping_approved_request')],
            ]);
        }

        $year = (int) Carbon::parse($startDate)->format('Y');
        $month = (int) Carbon::parse($startDate)->format('m');
        $balance = $this->leaveBalanceService->getOrCreateBalance($employeeId, $leaveTypeId, $year);
        $balance->loadMissing('leaveType');

        if ($balance->leaveType?->is_paid) {
            $this->assertPaidBalance($balance, $calculatedDays, $existing);
        }

        if (! $this->leaveBalanceService->checkMonthlyLimit($employeeId, $leaveTypeId, $year, $month, $calculatedDays)) {
            throw ValidationException::withMessages([
                'general' => [__('hr::hr.monthly_limit_exceeded')],
            ]);
        }

        $employee = Employee::query()->with('department')->findOrFail($employeeId);
        $departmentId = $employee->department_id ? (int) $employee->department_id : null;
        $hasPercentageLimit = $this->leaveBalanceService->checkLeavePercentageLimit(
            $employeeId,
            $startDate,
            $endDate,
            $departmentId,
            $excludeId
        );

        if (! $hasPercentageLimit) {
            $hasDepartmentPercentage = $employee->department && ! is_null($employee->department->max_leave_percentage);
            $hasCompanyPercentage = ! is_null(HRSetting::getCompanyMaxLeavePercentage());

            throw ValidationException::withMessages([
                'general' => [
                    (! $hasDepartmentPercentage && ! $hasCompanyPercentage)
                        ? __('hr::hr.no_leave_percentage_set')
                        : __('hr::hr.leave_percentage_exceeded'),
                ],
            ]);
        }
    }

    private function assertPaidBalance(EmployeeLeaveBalance $balance, float $newDays, ?LeaveRequest $existing): void
    {
        if ($existing === null) {
            if (! $balance->hasSufficientBalance($newDays)) {
                throw ValidationException::withMessages([
                    'general' => [__('hr::hr.insufficient_balance')],
                ]);
            }

            return;
        }

        $originalDays = (float) $existing->duration_days;
        $additionalDaysNeeded = $newDays - $originalDays;

        if ($existing->isApproved()) {
            $availableBalance = $balance->remaining_days + $balance->pending_days;
            if ($newDays > $availableBalance) {
                throw ValidationException::withMessages([
                    'general' => [__('hr::hr.insufficient_balance_for_edit')],
                ]);
            }

            return;
        }

        if ($existing->isSubmitted()) {
            if ($additionalDaysNeeded > 0) {
                $availableAfterCurrentPending = $balance->remaining_days + $balance->pending_days;
                if ($newDays > $availableAfterCurrentPending) {
                    throw ValidationException::withMessages([
                        'general' => [__('hr::hr.insufficient_balance_for_edit')],
                    ]);
                }
            }

            return;
        }

        if (! $balance->hasSufficientBalance($newDays)) {
            throw ValidationException::withMessages([
                'general' => [__('hr::hr.insufficient_balance_for_edit')],
            ]);
        }
    }
}
