<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HR\Models\WorkPermission;
use Modules\HR\Services\WorkPermissionService;

class WorkPermissionController extends Controller
{
    public function __construct(
        private readonly WorkPermissionService $workPermissionService
    ) {
        $this->middleware('can:view Work Permissions')->only(['index', 'data', 'show']);
        $this->middleware('can:create Work Permissions')->only(['store']);
        $this->middleware('can:edit Work Permissions')->only(['update']);
        $this->middleware('can:delete Work Permissions')->only(['destroy']);
    }

    public function index(): View
    {
        return view('hr::hr-management.work-permissions.manage-work-permissions', [
            'employees' => $this->workPermissionService->employeesForSelect(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $items = $this->workPermissionService->list(
            $request->string('search')->toString() ?: null,
            $request->filled('employee_id') ? (int) $request->input('employee_id') : null,
            $request->string('status')->toString() ?: null,
            $request->string('start_date')->toString() ?: null,
            $request->string('end_date')->toString() ?: null
        );

        return response()->json(['data' => $items]);
    }

    public function show(WorkPermission $workPermission): JsonResponse
    {
        $workPermission->load(['employee', 'created_by', 'updated_by', 'approved_by']);

        return response()->json(['data' => $workPermission]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'status' => 'nullable|in:pending,approved,rejected',
        ]);

        $this->workPermissionService->create($validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.work_permission_created_successfully'),
        ]);
    }

    public function update(Request $request, WorkPermission $workPermission): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'status' => 'nullable|in:pending,approved,rejected',
        ]);

        $this->workPermissionService->update($workPermission, $validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.work_permission_updated_successfully'),
        ]);
    }

    public function destroy(WorkPermission $workPermission): JsonResponse
    {
        $this->workPermissionService->delete($workPermission);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.work_permission_deleted_successfully'),
        ]);
    }

    public function approve(WorkPermission $workPermission): JsonResponse
    {
        abort_unless(auth()->user()?->can('Leave Approvals'), 403, __('hr::hr.unauthorized_action'));

        if ($workPermission->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => __('hr::hr.unauthorized_action'),
            ], 403);
        }

        $this->workPermissionService->approve($workPermission);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.work_permission_approved_successfully'),
        ]);
    }

    public function reject(WorkPermission $workPermission): JsonResponse
    {
        abort_unless(auth()->user()?->can('Leave Rejections'), 403, __('hr::hr.unauthorized_action'));

        if ($workPermission->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => __('hr::hr.unauthorized_action'),
            ], 403);
        }

        $this->workPermissionService->reject($workPermission);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.work_permission_rejected_successfully'),
        ]);
    }
}
