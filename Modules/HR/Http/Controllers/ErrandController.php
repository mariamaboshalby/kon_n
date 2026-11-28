<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HR\Models\Errand;
use Modules\HR\Services\ErrandService;

class ErrandController extends Controller
{
    public function __construct(
        private readonly ErrandService $errandService
    ) {
        $this->middleware('can:view Errands')->only(['index', 'data', 'show']);
        $this->middleware('can:create Errands')->only(['store']);
        $this->middleware('can:edit Errands')->only(['update']);
        $this->middleware('can:delete Errands')->only(['destroy']);
        $this->middleware('can:approve Errands')->only(['approve']);
    }

    public function index(): View
    {
        return view('hr::hr-management.errands.manage-errands', [
            'employees' => $this->errandService->employeesForSelect(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $items = $this->errandService->list(
            $request->string('search')->toString() ?: null,
            $request->filled('employee_id') ? (int) $request->input('employee_id') : null,
            $request->string('start_date')->toString() ?: null,
            $request->string('end_date')->toString() ?: null
        );

        return response()->json(['data' => $items]);
    }

    public function show(Errand $errand): JsonResponse
    {
        $errand->load(['employee', 'created_by', 'updated_by', 'approved_by']);

        return response()->json(['data' => $errand]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateErrand($request);
        $this->errandService->create($validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.errand_created_successfully'),
        ]);
    }

    public function update(Request $request, Errand $errand): JsonResponse
    {
        if ($errand->approved_at) {
            return response()->json([
                'success' => false,
                'message' => __('hr::hr.unauthorized_action'),
            ], 403);
        }

        $validated = $this->validateErrand($request);
        $this->errandService->update($errand, $validated);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.errand_updated_successfully'),
        ]);
    }

    public function destroy(Errand $errand): JsonResponse
    {
        $this->errandService->delete($errand);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.errand_deleted_successfully'),
        ]);
    }

    public function approve(Errand $errand): JsonResponse
    {
        if ($errand->approved_at) {
            return response()->json([
                'success' => false,
                'message' => __('hr::hr.unauthorized_action'),
            ], 403);
        }

        $this->errandService->approve($errand);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.errand_approved_successfully'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateErrand(Request $request): array
    {
        return $request->validate([
            'title' => 'nullable|string|max:255',
            'details' => 'nullable|string|max:2000',
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
    }
}
