<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HR\Models\Department;
use Modules\HR\Models\HRSetting;
use Modules\HR\Services\DepartmentService;

class DepartmentController extends Controller
{
    public function __construct(
        private readonly DepartmentService $departmentService
    ) {
        $this->middleware('can:view Departments')->only(['index', 'data', 'hierarchy']);
        $this->middleware('can:create Departments')->only(['store']);
        $this->middleware('can:edit Departments')->only(['update']);
        $this->middleware('can:delete Departments')->only(['destroy']);
    }

    public function index(): View
    {
        return view('hr::hr-management.departments.manage-department', [
            'employees' => $this->departmentService->employeesForSelect(),
            'companyMaxLeavePercentage' => HRSetting::getCompanyMaxLeavePercentage(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->departmentService->list($request->string('search')->toString() ?: null),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateDepartment($request);

        $this->departmentService->create($validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.department_created_successfully'),
        ]);
    }

    public function update(Request $request, Department $department): JsonResponse
    {
        $validated = $this->validateDepartment($request, $department->id);

        $this->departmentService->update($department, $validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.department_updated_successfully'),
        ]);
    }

    public function destroy(Department $department): JsonResponse
    {
        $this->departmentService->delete($department);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.department_deleted_successfully'),
        ]);
    }

    public function hierarchy(Department $department): View
    {
        $department = $this->departmentService->hierarchy($department);

        return view('hr::hr-management.departments.partials.hierarchy-tree', [
            'department' => $department,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateDepartment(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'title' => 'required|string|min:2|max:255|unique:departments,title,'.($ignoreId ?? 'NULL').',id',
            'parent_id' => 'nullable|exists:departments,id',
            'director_id' => 'nullable|exists:employees,id',
            'deputy_director_id' => 'nullable|exists:employees,id',
            'description' => 'nullable|string|max:255',
            'max_leave_percentage' => 'nullable|numeric|min:0|max:100',
        ]);
    }
}
