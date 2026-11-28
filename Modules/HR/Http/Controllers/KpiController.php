<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HR\Models\Kpi;
use Modules\HR\Services\EmployeeEvaluationService;
use Modules\HR\Services\KpiService;

class KpiController extends Controller
{
    public function __construct(
        private readonly KpiService $kpiService,
        private readonly EmployeeEvaluationService $evaluationService
    ) {
        $this->middleware('can:view KPIs')->only(['index', 'data']);
        $this->middleware('can:create KPIs')->only(['store']);
        $this->middleware('can:edit KPIs')->only(['update']);
        $this->middleware('can:delete KPIs')->only(['destroy']);
        $this->middleware('can:view Employee Evaluations')->only(['employeeEvaluation']);
    }

    public function index(): View
    {
        return view('hr::hr-management.kpis.manage-kpi');
    }

    public function employeeEvaluation(): View
    {
        return view('hr::hr-management.kpis.manage-employee-evaluation', [
            'employees' => $this->evaluationService->employeesForSelect(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->kpiService->list(
                $request->string('search')->toString() ?: null,
                $request->string('date_from')->toString() ?: null,
                $request->string('date_to')->toString()   ?: null,
            ),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $this->kpiService->create($validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.kpi_created_successfully'),
        ]);
    }

    public function update(Request $request, Kpi $kpi): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $this->kpiService->update($kpi, $validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.kpi_updated_successfully'),
        ]);
    }

    public function destroy(Kpi $kpi): JsonResponse
    {
        $this->kpiService->delete($kpi);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.kpi_deleted_successfully'),
        ]);
    }
}
