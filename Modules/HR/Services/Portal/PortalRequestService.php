<?php

declare(strict_types=1);

namespace Modules\HR\Services\Portal;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\HR\Models\AttendanceCorrectionRequest;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeeAdvance;
use Modules\HR\Models\EmployeeDocumentRequest;
use Modules\HR\Models\Errand;
use Modules\HR\Models\LeaveRequest;
use Modules\HR\Models\LeaveType;
use Modules\HR\Models\PortalRequest;
use Modules\HR\Models\PortalRequestAction;
use Modules\HR\Models\WorkPermission;
use Modules\HR\Services\LeaveRequestService;

final class PortalRequestService
{
    /** أنواع يكون اعتمادها النهائي عند HR دائماً (قيد محاسبي / رفع مستند). */
    public const HR_FINAL_TYPES = [PortalRequest::TYPE_ADVANCE, PortalRequest::TYPE_DOCUMENT];

    public const PERMISSION_TYPES = ['late_arrival', 'early_leave', 'during_shift'];

    public function __construct(
        private readonly LeaveRequestService $leaveRequestService,
        private readonly PortalNotificationService $notifications
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Employee $employee, string $type, array $data, ?string $clientRequestId): PortalRequest
    {
        if ($clientRequestId) {
            $existing = PortalRequest::query()
                ->where('employee_id', $employee->id)
                ->where('client_request_id', $clientRequestId)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $settings = PortalSettings::current();
        [$stage, $managerId] = $this->initialStage($employee, $settings);

        try {
            $request = DB::transaction(function () use ($employee, $type, $data, $clientRequestId, $stage, $managerId): PortalRequest {
                $source = $this->createSource($employee, $type, $data);

                $request = PortalRequest::create([
                    'employee_id' => $employee->id,
                    'type' => $type,
                    'requestable_type' => $source::class,
                    'requestable_id' => $source->getKey(),
                    'stage' => $stage,
                    'status' => PortalRequest::STATUS_PENDING,
                    'manager_employee_id' => $managerId,
                    'client_request_id' => $clientRequestId,
                    'summary' => $this->summaryFor($type, $source),
                ]);

                $this->logAction(
                    $request,
                    'employee',
                    'submitted',
                    PortalRequestAction::ACTOR_EMPLOYEE,
                    (int) $employee->id,
                    (string) $employee->name
                );

                return $request;
            });
        } catch (QueryException $e) {
            if ($clientRequestId) {
                $existing = PortalRequest::query()
                    ->where('employee_id', $employee->id)
                    ->where('client_request_id', $clientRequestId)
                    ->first();
                if ($existing) {
                    return $existing;
                }
            }

            throw $e;
        }

        $request->setRelation('employee', $employee);

        if ($request->stage === PortalRequest::STAGE_MANAGER) {
            $this->notifications->notifyManagerNewRequest($request);
        } else {
            $this->notifications->notifyHrNewRequest($request);
        }

        return $request;
    }

    public function cancel(Employee $employee, PortalRequest $request): PortalRequest
    {
        $this->syncFromSource($request);

        if ((int) $request->employee_id !== (int) $employee->id) {
            throw new PortalException(__('hr::portal.request_not_found'), 'not_found', 404);
        }

        if (! $request->isPending()) {
            throw new PortalException(__('hr::portal.request_cannot_cancel'), 'cannot_cancel', 409);
        }

        DB::transaction(function () use ($employee, $request): void {
            $source = $request->requestable;

            if ($source instanceof LeaveRequest) {
                if ($source->canBeCancelled()) {
                    $this->leaveRequestService->cancel($source);
                }
            } elseif ($source instanceof WorkPermission) {
                if ($source->status === 'pending') {
                    $source->delete();
                }
            } elseif ($source instanceof Errand) {
                if ($source->approved_at === null) {
                    $source->delete();
                }
            } elseif ($source instanceof EmployeeAdvance) {
                if ($source->isPending()) {
                    $source->delete();
                }
            } elseif ($source instanceof AttendanceCorrectionRequest || $source instanceof EmployeeDocumentRequest) {
                $source->update(['status' => 'cancelled']);
            }

            $request->update([
                'status' => PortalRequest::STATUS_CANCELLED,
                'stage' => PortalRequest::STAGE_DONE,
                'decided_at' => now(),
            ]);

            $this->logAction(
                $request,
                'employee',
                'cancelled',
                PortalRequestAction::ACTOR_EMPLOYEE,
                (int) $employee->id,
                (string) $employee->name
            );
        });

        return $request->fresh() ?? $request;
    }

    /**
     * @param  array{status?: string|null, type?: string|null}  $filters
     * @return array{data: list<array<string, mixed>>, has_more: bool, next_page: int|null}
     */
    public function listForEmployee(Employee $employee, array $filters, int $page): array
    {
        $query = PortalRequest::query()
            ->where('employee_id', $employee->id)
            ->when($filters['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($filters['type'] ?? null, fn (Builder $q, $v) => $q->where('type', $v))
            ->orderByDesc('id');

        return $this->paginate($query, $page, 20, false);
    }

    /**
     * @return array{data: list<array<string, mixed>>, has_more: bool, next_page: int|null}
     */
    public function listForManager(Employee $manager, int $page): array
    {
        $query = PortalRequest::query()
            ->with('employee:id,name')
            ->where('manager_employee_id', $manager->id)
            ->where('stage', PortalRequest::STAGE_MANAGER)
            ->where('status', PortalRequest::STATUS_PENDING)
            ->orderBy('id');

        return $this->paginate($query, $page, 20, true);
    }

    /**
     * @param  array{status?: string|null, stage?: string|null, type?: string|null, employee_id?: int|string|null, search?: string|null}  $filters
     * @return array{data: list<array<string, mixed>>, has_more: bool, next_page: int|null}
     */
    public function listForHr(array $filters, int $page): array
    {
        $search = trim((string) ($filters['search'] ?? ''));

        $query = PortalRequest::query()
            ->with('employee:id,name', 'manager:id,name')
            ->when($filters['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($filters['stage'] ?? null, fn (Builder $q, $v) => $q->where('stage', $v))
            ->when($filters['type'] ?? null, fn (Builder $q, $v) => $q->where('type', $v))
            ->when($filters['employee_id'] ?? null, fn (Builder $q, $v) => $q->where('employee_id', (int) $v))
            ->when($search !== '', fn (Builder $q) => $q->whereHas(
                'employee',
                fn (Builder $e) => $e->where('name', 'like', '%'.$search.'%')
            ))
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('id');

        return $this->paginate($query, $page, 25, true);
    }

    /**
     * يحدّث الغلاف لو اعتمد/رفض HR الطلب من الشاشات القديمة مباشرة.
     */
    public function syncFromSource(PortalRequest $request): void
    {
        if (! $request->isPending()) {
            return;
        }

        $request->loadMissing('requestable');
        $status = $this->sourceStatus($request);
        if ($status === null) {
            return;
        }

        $request->update([
            'status' => $status,
            'stage' => PortalRequest::STAGE_DONE,
            'decided_at' => now(),
        ]);

        $this->logAction($request, 'hr', $status, PortalRequestAction::ACTOR_USER, null, null);
        $this->notifications->notifyEmployeeDecision($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(PortalRequest $request, bool $withEmployee = false): array
    {
        $data = [
            'id' => (int) $request->id,
            'type' => $request->type,
            'type_label' => __('hr::portal.type_'.$request->type),
            'summary' => (string) $request->summary,
            'status' => $request->status,
            'status_label' => __('hr::portal.status_'.$request->status),
            'stage' => $request->stage,
            'stage_label' => __('hr::portal.stage_'.$request->stage),
            'created_at' => $request->created_at?->format('Y-m-d H:i'),
            'decided_at' => $request->decided_at?->format('Y-m-d H:i'),
            'can_cancel' => $request->isPending(),
        ];

        if ($withEmployee) {
            $data['employee'] = [
                'id' => (int) $request->employee_id,
                'name' => $request->employee?->name,
            ];
            $data['manager'] = $request->manager?->name;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(PortalRequest $request, bool $withEmployee = false): array
    {
        $this->syncFromSource($request);
        $request->loadMissing(['requestable', 'actions', 'employee:id,name', 'manager:id,name']);

        $source = $request->requestable;

        return array_merge($this->serialize($request, $withEmployee), [
            'details' => $source ? $this->detailsFor($request->type, $source) : [],
            'document_available' => $source instanceof EmployeeDocumentRequest && $source->hasFile() && $source->status === 'approved',
            'timeline' => $request->actions->map(fn (PortalRequestAction $a) => [
                'stage' => $a->stage,
                'stage_label' => __('hr::portal.stage_'.$a->stage),
                'action' => $a->action,
                'action_label' => __('hr::portal.action_'.$a->action),
                'actor' => $a->actor_name,
                'note' => $a->note,
                'at' => $a->created_at?->format('Y-m-d H:i'),
            ])->values(),
        ]);
    }

    public function logAction(
        PortalRequest $request,
        string $stage,
        string $action,
        string $actorType,
        ?int $actorId,
        ?string $actorName,
        ?string $note = null
    ): void {
        PortalRequestAction::create([
            'portal_request_id' => $request->id,
            'stage' => $stage,
            'action' => $action,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'actor_name' => $actorName,
            'note' => $note,
            'created_at' => now(),
        ]);
    }

    /**
     * @return array{0: string, 1: int|null}
     */
    private function initialStage(Employee $employee, PortalSettings $settings): array
    {
        if ($settings->approvalFlow() === PortalSettings::FLOW_HR_ONLY || ! $employee->line_manager_id) {
            return [PortalRequest::STAGE_HR, null];
        }

        $manager = Employee::query()->find($employee->line_manager_id);
        $eligible = $manager
            && $manager->portal_enabled
            && ($manager->getAttributes()['status'] ?? null) !== 'خارج الشركة'
            && $manager->date_of_fire === null;

        return $eligible
            ? [PortalRequest::STAGE_MANAGER, (int) $manager->id]
            : [PortalRequest::STAGE_HR, null];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createSource(Employee $employee, string $type, array $data): Model
    {
        return match ($type) {
            PortalRequest::TYPE_LEAVE => $this->createLeave($employee, $data),
            PortalRequest::TYPE_PERMISSION => WorkPermission::create([
                'employee_id' => $employee->id,
                'date' => $data['date'],
                'permission_type' => $data['permission_type'],
                'from_time' => $data['from_time'] ?? null,
                'to_time' => $data['to_time'] ?? null,
                'reason' => $data['reason'] ?? null,
                'status' => 'pending',
            ]),
            PortalRequest::TYPE_ERRAND => Errand::create([
                'employee_id' => $employee->id,
                'title' => $data['title'],
                'details' => $data['details'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
            ]),
            PortalRequest::TYPE_ADVANCE => EmployeeAdvance::create([
                'employee_id' => $employee->id,
                'amount' => $data['amount'],
                'date' => now()->toDateString(),
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
            ]),
            PortalRequest::TYPE_ATTENDANCE_CORRECTION => AttendanceCorrectionRequest::create([
                'employee_id' => $employee->id,
                'date' => $data['date'],
                'time' => $data['time'],
                'type' => $data['attendance_type'],
                'reason' => $data['reason'],
                'status' => 'pending',
            ]),
            PortalRequest::TYPE_DOCUMENT => EmployeeDocumentRequest::create([
                'employee_id' => $employee->id,
                'document_type' => $data['document_type'],
                'purpose' => $data['purpose'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
            ]),
            default => throw new PortalException(__('hr::portal.invalid_request_type'), 'invalid_type', 422),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createLeave(Employee $employee, array $data): LeaveRequest
    {
        $leave = $this->leaveRequestService->create([
            'employee_id' => $employee->id,
            'leave_type_id' => (int) $data['leave_type_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'] ?? null,
        ]);

        $this->leaveRequestService->submit($leave);

        return $leave->refresh();
    }

    private function summaryFor(string $type, Model $source): string
    {
        $summary = match (true) {
            $source instanceof LeaveRequest => trim(
                (LeaveType::query()->whereKey($source->leave_type_id)->value('name') ?? __('hr::portal.type_leave'))
                .' · '.__('hr::portal.days_count', ['count' => (float) $source->duration_days])
                .' · '.$source->start_date?->format('Y-m-d').' → '.$source->end_date?->format('Y-m-d')
            ),
            $source instanceof WorkPermission => __('hr::portal.permission_type_'.($source->permission_type ?: 'during_shift'))
                .' · '.Carbon::parse($source->date)->format('Y-m-d')
                .($source->from_time ? ' '.substr((string) $source->from_time, 0, 5) : '')
                .($source->to_time ? ' - '.substr((string) $source->to_time, 0, 5) : ''),
            $source instanceof Errand => (string) $source->title
                .' · '.Carbon::parse($source->start_date)->format('Y-m-d').' → '.Carbon::parse($source->end_date)->format('Y-m-d'),
            $source instanceof EmployeeAdvance => number_format((float) $source->amount, 2).' · '.(string) $source->reason,
            $source instanceof AttendanceCorrectionRequest => __('hr::portal.att_'.$source->type)
                .' · '.$source->date?->format('Y-m-d').' '.substr((string) $source->time, 0, 5),
            $source instanceof EmployeeDocumentRequest => __('hr::portal.document_type_'.$source->document_type),
            default => __('hr::portal.type_'.$type),
        };

        return mb_substr($summary, 0, 250);
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function detailsFor(string $type, Model $source): array
    {
        $rows = match (true) {
            $source instanceof LeaveRequest => [
                ['hr::portal.field_leave_type', LeaveType::query()->whereKey($source->leave_type_id)->value('name')],
                ['hr::portal.field_start_date', $source->start_date?->format('Y-m-d')],
                ['hr::portal.field_end_date', $source->end_date?->format('Y-m-d')],
                ['hr::portal.field_days', (string) (float) $source->duration_days],
                ['hr::portal.field_reason', $source->reason],
            ],
            $source instanceof WorkPermission => [
                ['hr::portal.field_permission_type', __('hr::portal.permission_type_'.($source->permission_type ?: 'during_shift'))],
                ['hr::portal.field_date', Carbon::parse($source->date)->format('Y-m-d')],
                ['hr::portal.field_from_time', $source->from_time ? substr((string) $source->from_time, 0, 5) : null],
                ['hr::portal.field_to_time', $source->to_time ? substr((string) $source->to_time, 0, 5) : null],
                ['hr::portal.field_reason', $source->reason],
            ],
            $source instanceof Errand => [
                ['hr::portal.field_title', $source->title],
                ['hr::portal.field_start_date', Carbon::parse($source->start_date)->format('Y-m-d')],
                ['hr::portal.field_end_date', Carbon::parse($source->end_date)->format('Y-m-d')],
                ['hr::portal.field_details', $source->details],
            ],
            $source instanceof EmployeeAdvance => [
                ['hr::portal.field_amount', number_format((float) $source->amount, 2)],
                ['hr::portal.field_reason', $source->reason],
                ['hr::portal.field_notes', $source->notes],
            ],
            $source instanceof AttendanceCorrectionRequest => [
                ['hr::portal.field_attendance_type', __('hr::portal.att_'.$source->type)],
                ['hr::portal.field_date', $source->date?->format('Y-m-d')],
                ['hr::portal.field_time', substr((string) $source->time, 0, 5)],
                ['hr::portal.field_reason', $source->reason],
            ],
            $source instanceof EmployeeDocumentRequest => [
                ['hr::portal.field_document_type', __('hr::portal.document_type_'.$source->document_type)],
                ['hr::portal.field_purpose', $source->purpose],
                ['hr::portal.field_notes', $source->notes],
            ],
            default => [],
        };

        return array_values(array_map(
            fn (array $row) => ['label' => __($row[0]), 'value' => (string) ($row[1] ?? '—')],
            array_filter($rows, fn (array $row) => $row[1] !== null && $row[1] !== '')
        ));
    }

    private function sourceStatus(PortalRequest $request): ?string
    {
        $source = $request->requestable;

        if ($source === null) {
            return PortalRequest::STATUS_CANCELLED;
        }

        $raw = match (true) {
            $source instanceof LeaveRequest => $source->status,
            $source instanceof Errand => $source->approved_at !== null ? 'approved' : 'pending',
            default => (string) ($source->status ?? 'pending'),
        };

        return in_array($raw, ['approved', 'rejected', 'cancelled'], true) ? $raw : null;
    }

    /**
     * @return array{data: list<array<string, mixed>>, has_more: bool, next_page: int|null}
     */
    private function paginate(Builder $query, int $page, int $perPage, bool $withEmployee): array
    {
        $paginator = $query->with('requestable')->simplePaginate($perPage, ['*'], 'page', max(1, $page));

        $items = collect($paginator->items())->map(function (PortalRequest $request) use ($withEmployee) {
            $this->syncFromSource($request);

            return $this->serialize($request, $withEmployee);
        })->values()->all();

        return [
            'data' => $items,
            'has_more' => $paginator->hasMorePages(),
            'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
        ];
    }
}
