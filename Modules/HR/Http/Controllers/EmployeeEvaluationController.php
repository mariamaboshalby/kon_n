<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HR\Models\Employee;
use Modules\HR\Services\EmployeeEvaluationService;

class EmployeeEvaluationController extends Controller
{
    public function __construct(
        private readonly EmployeeEvaluationService $evaluationService
    ) {
        $this->middleware('can:view Employee Evaluations')->only(['index', 'data', 'show', 'employeeKpis']);
        $this->middleware('can:create Employee Evaluations')->only(['store']);
        $this->middleware('can:edit Employee Evaluations')->only(['update']);
        $this->middleware('can:delete Employee Evaluations')->only(['destroy']);
    }

    public function index(): View
    {
        return view('hr::hr-management.kpis.manage-employee-evaluation', [
            'employees' => $this->evaluationService->employeesForSelect(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->evaluationService->list(
                $request->string('search')->toString()      ?: null,
                $request->integer('employee_id')            ?: null,
                $request->string('final_rating')->toString() ?: null,
                $request->string('date_from')->toString()   ?: null,
                $request->string('date_to')->toString()     ?: null,
            ),
        ]);
    }

    public function show(int $evaluation): JsonResponse
    {
        return response()->json([
            'data' => $this->evaluationService->find($evaluation),
        ]);
    }

    public function employeeKpis(Employee $employee): JsonResponse
    {
        $employee->load(['job', 'department']);

        return response()->json([
            'data' => $this->evaluationService->employeeKpis($employee->id),
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'job_title' => $employee->job?->title,
                'department' => $employee->department?->title,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateEvaluation($request);
        $this->evaluationService->create($validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.evaluation_saved_successfully'),
        ]);
    }

    public function update(Request $request, int $evaluation): JsonResponse
    {
        $model = $this->evaluationService->find($evaluation);
        $validated = $this->validateEvaluation($request);
        $this->evaluationService->update($model, $validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.evaluation_updated_successfully'),
        ]);
    }

    public function destroy(int $evaluation): JsonResponse
    {
        $model = $this->evaluationService->find($evaluation);
        $this->evaluationService->delete($model);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.evaluation_deleted_successfully'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateEvaluation(Request $request): array
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'evaluation_date' => 'required|date',
            'evaluation_period_from' => 'required|date',
            'evaluation_period_to' => 'required|date|after:evaluation_period_from',
            'direct_manager' => 'nullable|string|max:255',
            'scores' => 'required|array',
            'scores.*' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|array',
            'notes.*' => 'nullable|string|max:1000',
        ]);

        $validated['direct_manager'] = $validated['direct_manager'] ?? '';

        return $validated;
    }
}
