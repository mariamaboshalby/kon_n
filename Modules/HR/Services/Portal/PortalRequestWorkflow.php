<?php

declare(strict_types=1);

namespace Modules\HR\Services\Portal;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\HR\Models\Attendance;
use Modules\HR\Models\AttendanceCorrectionRequest;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeeAdvance;
use Modules\HR\Models\EmployeeDocumentRequest;
use Modules\HR\Models\Errand;
use Modules\HR\Models\LeaveRequest;
use Modules\HR\Models\PortalRequest;
use Modules\HR\Models\PortalRequestAction;
use Modules\HR\Models\WorkPermission;
use Modules\HR\Services\EmployeeAdvanceApprovalService;
use Modules\HR\Services\ErrandService;
use Modules\HR\Services\LeaveRequestService;
use Modules\HR\Services\WorkPermissionService;
use RuntimeException;

/**
 * مراحل الاعتماد: المدير المباشر (موظف من التطبيق) ← HR (مستخدم من اللوحة).
 */
final class PortalRequestWorkflow
{
    public function __construct(
        private readonly PortalRequestService $requests,
        private readonly PortalNotificationService $notifications,
        private readonly LeaveRequestService $leaveRequestService,
        private readonly WorkPermissionService $workPermissionService,
        private readonly ErrandService $errandService,
        private readonly EmployeeAdvanceApprovalService $advanceApprovalService
    ) {
    }

    public function managerDecide(Employee $manager, PortalRequest $request, bool $approve, ?string $note): PortalRequest
    {
        $this->requests->syncFromSource($request);

        if ((int) $request->manager_employee_id !== (int) $manager->id
            || $request->stage !== PortalRequest::STAGE_MANAGER) {
            throw new PortalException(__('hr::portal.request_not_found'), 'not_found', 404);
        }

        if (! $request->isPending()) {
            throw new PortalException(__('hr::portal.request_already_decided'), 'already_decided', 409);
        }

        if (! $approve && trim((string) $note) === '') {
            throw new PortalException(__('hr::portal.reject_reason_required'), 'reason_required', 422);
        }

        $settings = PortalSettings::current();
        $finalHere = $approve
            && $settings->approvalFlow() === PortalSettings::FLOW_MANAGER_ONLY
            && ! in_array($request->type, PortalRequestService::HR_FINAL_TYPES, true);

        DB::transaction(function () use ($manager, $request, $approve, $note, $finalHere): void {
            if (! $approve) {
                $this->rejectSource($request);
                $request->update([
                    'status' => PortalRequest::STATUS_REJECTED,
                    'stage' => PortalRequest::STAGE_DONE,
                    'decided_at' => now(),
                ]);
            } elseif ($finalHere) {
                $this->approveSource($request, null);
                $request->update([
                    'status' => PortalRequest::STATUS_APPROVED,
                    'stage' => PortalRequest::STAGE_DONE,
                    'decided_at' => now(),
                ]);
            } else {
                $request->update(['stage' => PortalRequest::STAGE_HR]);
            }

            $this->requests->logAction(
                $request,
                PortalRequest::STAGE_MANAGER,
                $approve ? 'approved' : 'rejected',
                PortalRequestAction::ACTOR_EMPLOYEE,
                (int) $manager->id,
                (string) $manager->name,
                $note
            );
        });

        $request->refresh();
        $this->notifications->notifyEmployeeDecision($request);

        if ($request->isPending() && $request->stage === PortalRequest::STAGE_HR) {
            $this->notifications->notifyHrNewRequest($request->loadMissing('employee'));
        }

        return $request;
    }

    /**
     * HR يقرر في أي مرحلة معلّقة (تجاوز المدير مسموح).
     */
    public function hrDecide(User $user, PortalRequest $request, bool $approve, ?string $note, ?UploadedFile $document): PortalRequest
    {
        $this->requests->syncFromSource($request);

        if (! $request->isPending()) {
            throw new PortalException(__('hr::portal.request_already_decided'), 'already_decided', 409);
        }

        if (! $approve && trim((string) $note) === '') {
            throw new PortalException(__('hr::portal.reject_reason_required'), 'reason_required', 422);
        }

        $previousStage = $request->stage;

        DB::transaction(function () use ($user, $request, $approve, $note, $document, $previousStage): void {
            if ($approve) {
                $this->approveSource($request, $document);
            } else {
                $this->rejectSource($request);
            }

            $request->update([
                'status' => $approve ? PortalRequest::STATUS_APPROVED : PortalRequest::STATUS_REJECTED,
                'stage' => PortalRequest::STAGE_DONE,
                'decided_at' => now(),
            ]);

            $this->requests->logAction(
                $request,
                $previousStage === PortalRequest::STAGE_MANAGER ? PortalRequest::STAGE_HR : $previousStage,
                $approve ? 'approved' : 'rejected',
                PortalRequestAction::ACTOR_USER,
                (int) $user->id,
                (string) $user->name,
                $note
            );
        });

        $request->refresh();
        $this->notifications->notifyEmployeeDecision($request);

        return $request;
    }

    /**
     * رفع/استبدال ملف مستند معتمد.
     */
    public function attachDocument(PortalRequest $request, UploadedFile $file): void
    {
        $source = $request->requestable;
        if (! $source instanceof EmployeeDocumentRequest) {
            throw new PortalException(__('hr::portal.invalid_request_type'), 'invalid_type', 422);
        }

        $this->storeDocument($source, $file);
    }

    private function approveSource(PortalRequest $request, ?UploadedFile $document): void
    {
        $request->loadMissing('requestable');
        $source = $request->requestable;

        if ($source === null) {
            throw new PortalException(__('hr::portal.request_source_missing'), 'source_missing', 409);
        }

        try {
            match (true) {
                $source instanceof LeaveRequest => $this->approveLeave($source),
                $source instanceof WorkPermission => $this->workPermissionService->approve($source),
                $source instanceof Errand => $this->errandService->approve($source),
                $source instanceof EmployeeAdvance => $this->advanceApprovalService->approve($source),
                $source instanceof AttendanceCorrectionRequest => $this->approveCorrection($source),
                $source instanceof EmployeeDocumentRequest => $this->approveDocument($source, $document),
                default => throw new PortalException(__('hr::portal.invalid_request_type'), 'invalid_type', 422),
            };
        } catch (RuntimeException $e) {
            if ($e instanceof PortalException) {
                throw $e;
            }

            throw new PortalException($e->getMessage(), 'approval_failed', 422);
        }
    }

    private function rejectSource(PortalRequest $request): void
    {
        $request->loadMissing('requestable');
        $source = $request->requestable;

        if ($source === null) {
            return;
        }

        if ($source instanceof LeaveRequest) {
            if ($source->canBeRejected()) {
                $this->leaveRequestService->reject($source, Auth::id());
            }
        } elseif ($source instanceof WorkPermission) {
            $this->workPermissionService->reject($source);
        } elseif ($source instanceof EmployeeAdvance) {
            $this->advanceApprovalService->reject($source);
        } elseif ($source instanceof AttendanceCorrectionRequest || $source instanceof EmployeeDocumentRequest) {
            $source->update([
                'status' => 'rejected',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);
        }
    }

    private function approveLeave(LeaveRequest $leave): void
    {
        try {
            $this->leaveRequestService->approve($leave, Auth::id());
        } catch (ValidationException $e) {
            $first = collect($e->errors())->flatten()->first();

            throw new PortalException((string) ($first ?: __('hr::hr.cannot_approve_leave_request')), 'approval_failed', 422);
        }
    }

    private function approveCorrection(AttendanceCorrectionRequest $correction): void
    {
        $employee = Employee::query()->find($correction->employee_id);
        if (! $employee) {
            throw new PortalException(__('hr::portal.request_source_missing'), 'source_missing', 409);
        }

        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'employee_attendance_finger_print_id' => $employee->finger_print_id,
            'employee_attendance_finger_print_name' => $employee->finger_print_name,
            'type' => $correction->type,
            'date' => $correction->date->toDateString(),
            'time' => substr((string) $correction->time, 0, 8),
            'status' => 'pending',
            'notes' => mb_substr(__('hr::portal.correction_note').': '.$correction->reason, 0, 250),
            'device_type' => 'correction',
            'user_id' => Auth::id(),
            'client_request_id' => 'correction-'.$correction->id,
        ]);

        $correction->update([
            'status' => 'approved',
            'attendance_id' => $attendance->id,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
    }

    private function approveDocument(EmployeeDocumentRequest $documentRequest, ?UploadedFile $file): void
    {
        if ($file) {
            $this->storeDocument($documentRequest, $file);
        }

        if (! $documentRequest->fresh()?->hasFile()) {
            throw new PortalException(__('hr::portal.document_file_required'), 'document_file_required', 422);
        }

        $documentRequest->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
    }

    private function storeDocument(EmployeeDocumentRequest $documentRequest, UploadedFile $file): void
    {
        $path = Storage::disk('local')->putFile('hr/portal/documents/'.now()->format('Y/m'), $file);

        if ($documentRequest->file_path) {
            Storage::disk('local')->delete($documentRequest->file_path);
        }

        $documentRequest->update([
            'file_path' => $path,
            'file_name' => mb_substr($file->getClientOriginalName(), 0, 190),
        ]);
    }
}
