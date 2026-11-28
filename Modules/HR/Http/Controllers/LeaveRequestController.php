<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\HR\Models\LeaveRequest;
use Modules\HR\Services\LeaveBalanceService;
use Modules\HR\Services\LeaveRequestService;

class LeaveRequestController extends Controller
{
    public function __construct(
        private readonly LeaveRequestService $leaveRequestService,
        private readonly LeaveBalanceService $leaveBalanceService
    ) {
        $this->middleware('can:view Leave Requests')->only([
            'index', 'data', 'trashed', 'trashedData', 'show', 'preview', 'approve', 'reject',
        ]);
        $this->middleware('can:create Leave Requests')->only(['create', 'store']);
        $this->middleware('can:edit Leave Requests')->only(['edit', 'update', 'submit', 'cancel']);
        $this->middleware('can:delete Leave Requests')->only(['destroy', 'restore', 'forceDestroy']);
    }

    public function index(): View
    {
        return view('hr::hr-management.leaves.leave-requests.index', $this->formSelects());
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->leaveRequestService->list($this->listFilters($request), false, Auth::user()),
        ]);
    }

    public function trashed(): View
    {
        return view('hr::hr-management.leaves.leave-requests.trashed', $this->formSelects());
    }

    public function trashedData(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->leaveRequestService->list($this->listFilters($request), true, Auth::user()),
        ]);
    }

    public function create(): View
    {
        return view('hr::hr-management.leaves.leave-requests.create', array_merge($this->formSelects(), [
            'startDate' => now()->format('Y-m-d'),
            'endDate' => now()->addDays(1)->format('Y-m-d'),
        ]));
    }

    public function store(Request $request): JsonResponse
    {
        $leaveRequest = $this->leaveRequestService->create($this->validateLeaveRequest($request));
        session()->flash('message', __('hr::hr.leave_request_created_successfully'));

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.leave_request_created_successfully'),
            'redirect' => route('hr.leaves.requests.show', $leaveRequest->id),
        ]);
    }

    public function show(LeaveRequest $leaveRequest): View
    {
        Gate::authorize('view', $leaveRequest);

        $leaveRequest->load(['employee', 'leaveType', 'approver']);
        $user = Auth::user();

        return view('hr::hr-management.leaves.leave-requests.show', [
            'leaveRequest' => $leaveRequest,
            'statusBadge' => $this->leaveRequestService->statusBadgeClass((string) $leaveRequest->status),
            'statusText' => $this->leaveRequestService->statusText((string) $leaveRequest->status),
            'statusDescription' => $this->leaveRequestService->statusDescription((string) $leaveRequest->status),
            'canSubmit' => $leaveRequest->isDraft() && ($user?->can('edit Leave Requests') ?? false),
            'canApprove' => $leaveRequest->isSubmitted() && $user && (
                $user->can('edit Leave Approvals') || $user->can('approve', $leaveRequest)
            ),
            'canReject' => $leaveRequest->isSubmitted() && $user && (
                $user->can('edit Leave Rejections') || $user->can('reject', $leaveRequest)
            ),
            'canEdit' => in_array($leaveRequest->status, ['draft', 'submitted'], true)
                && $user
                && $user->can('update', $leaveRequest),
            'canCancel' => $user && $user->can('cancel', $leaveRequest),
        ]);
    }

    public function edit(LeaveRequest $leaveRequest): View
    {
        Gate::authorize('update', $leaveRequest);

        $leaveRequest->load(['employee', 'leaveType']);

        return view('hr::hr-management.leaves.leave-requests.edit', array_merge($this->formSelects(), [
            'leaveRequest' => $leaveRequest,
        ]));
    }

    public function update(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        Gate::authorize('update', $leaveRequest);

        $this->leaveRequestService->update($leaveRequest, $this->validateLeaveRequest($request, true));
        session()->flash('message', __('hr::hr.leave_request_updated_successfully'));

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.leave_request_updated_successfully'),
            'redirect' => route('hr.leaves.requests.show', $leaveRequest->id),
        ]);
    }

    public function destroy(LeaveRequest $leaveRequest): JsonResponse
    {
        Gate::authorize('delete', $leaveRequest);
        $this->leaveRequestService->delete($leaveRequest);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.leave_deleted_successfully'),
        ]);
    }

    public function restore(LeaveRequest $leaveRequest): JsonResponse
    {
        Gate::authorize('restore', $leaveRequest);
        $this->leaveRequestService->restore($leaveRequest);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.leave_request_restored_successfully'),
        ]);
    }

    public function forceDestroy(LeaveRequest $leaveRequest): JsonResponse
    {
        Gate::authorize('forceDelete', $leaveRequest);
        $this->leaveRequestService->forceDelete($leaveRequest);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.leave_request_force_deleted_successfully'),
        ]);
    }

    public function submit(LeaveRequest $leaveRequest): JsonResponse
    {
        Gate::authorize('update', $leaveRequest);
        $this->leaveRequestService->submit($leaveRequest);
        session()->flash('message', __('hr::hr.leave_request_submitted_successfully'));

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.leave_request_submitted_successfully'),
            'redirect' => route('hr.leaves.requests.index'),
        ]);
    }

    public function approve(LeaveRequest $leaveRequest): JsonResponse
    {
        $user = Auth::user();
        if (! $user?->can('edit Leave Approvals')) {
            Gate::authorize('approve', $leaveRequest);
        }

        try {
            $this->leaveRequestService->approve($leaveRequest, (int) Auth::id());
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('hr::hr.error_approving_leave_request', ['error' => $e->getMessage()]),
            ], 422);
        }

        session()->flash('message', __('hr::hr.leave_approved_successfully'));

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.leave_approved_successfully'),
            'redirect' => route('hr.leaves.requests.index'),
        ]);
    }

    public function reject(LeaveRequest $leaveRequest): JsonResponse
    {
        $user = Auth::user();
        if (! $user?->can('edit Leave Rejections') && ! $user?->can('view Leave Rejections')) {
            Gate::authorize('reject', $leaveRequest);
        }

        $this->leaveRequestService->reject($leaveRequest, (int) Auth::id());
        session()->flash('message', __('hr::hr.leave_rejected_successfully'));

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.leave_rejected_successfully'),
            'redirect' => route('hr.leaves.requests.index'),
        ]);
    }

    public function cancel(LeaveRequest $leaveRequest): JsonResponse
    {
        Gate::authorize('cancel', $leaveRequest);
        $this->leaveRequestService->cancel($leaveRequest);
        session()->flash('message', __('hr::hr.leave_cancelled_successfully'));

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.leave_cancelled_successfully'),
            'redirect' => route('hr.leaves.requests.index'),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        return response()->json(
            $this->leaveRequestService->preview($request->only([
                'employee_id',
                'leave_type_id',
                'start_date',
                'end_date',
            ]))
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function formSelects(): array
    {
        return [
            'employees' => $this->leaveBalanceService->employeesForSelect(),
            'leaveTypes' => $this->leaveBalanceService->leaveTypesForSelect(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function listFilters(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString() ?: null,
            'employee_id' => $request->input('employee_id'),
            'leave_type_id' => $request->input('leave_type_id'),
            'status' => $request->string('status')->toString() ?: null,
            'date_from' => $request->string('date_from')->toString() ?: null,
            'date_to' => $request->string('date_to')->toString() ?: null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateLeaveRequest(Request $request, bool $isEdit = false): array
    {
        $startRule = 'required|date';

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => $startRule,
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:1000',
        ], [], [
            'employee_id' => __('hr::hr.employee'),
            'leave_type_id' => __('hr::hr.leave_type'),
            'start_date' => __('hr::hr.start_date'),
            'end_date' => __('hr::hr.end_date'),
            'reason' => __('hr::hr.reason'),
        ]);

        $validated['reason'] = isset($validated['reason']) && $validated['reason'] !== ''
            ? $validated['reason']
            : null;

        return $validated;
    }
}
