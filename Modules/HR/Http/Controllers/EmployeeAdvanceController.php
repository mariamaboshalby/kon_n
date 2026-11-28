<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\HR\Models\Department;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeeAdvance;
use Modules\HR\Services\EmployeeAdvanceApprovalService;

class EmployeeAdvanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view Employee Advances')->only(['index', 'data']);
        $this->middleware('can:create Employee Advances')->only(['store']);
        $this->middleware('can:edit Employee Advances')->only(['update', 'approve', 'reject']);
        $this->middleware('can:delete Employee Advances')->only(['destroy']);
    }

    public function index(): View
    {
        return view('hr::hr-management.employee-advances.index', [
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = EmployeeAdvance::query()
            ->with('employee:id,name');

        if ($request->filled('department_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department_id', (int) $request->input('department_id'));
            });
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', (int) $request->input('employee_id'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->input('end_date'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $items = (clone $query)->orderByDesc('created_at')->limit(300)->get();

        $summary = [
            'total_count' => $items->count(),
            'total_amount' => (float) $items->sum('amount'),
        ];

        return response()->json([
            'data' => $items,
            'summary' => $summary,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateAdvance($request);
        $data['created_by'] = Auth::id();
        $data['status'] = 'pending';
        EmployeeAdvance::create($data);

        return response()->json(['success' => true, 'message' => __('hr::hr.advance_created_successfully')]);
    }

    public function update(Request $request, EmployeeAdvance $advance): JsonResponse
    {
        if ($advance->isApproved()) {
            return response()->json(['success' => false, 'message' => __('hr::hr.cannot_edit_approved_advance')], 422);
        }

        $data = $this->validateAdvance($request);
        $data['updated_by'] = Auth::id();
        $advance->update($data);

        return response()->json(['success' => true, 'message' => __('hr::hr.advance_updated_successfully')]);
    }

    public function approve(EmployeeAdvance $advance): JsonResponse
    {
        if ($advance->isApproved()) {
            return response()->json(['success' => false, 'message' => __('hr::hr.advance_already_approved')], 422);
        }

        try {
            app(EmployeeAdvanceApprovalService::class)->approve($advance);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => __('hr::hr.advance_approved_successfully')]);
    }

    public function reject(EmployeeAdvance $advance): JsonResponse
    {
        if ($advance->isApproved()) {
            return response()->json(['success' => false, 'message' => __('hr::hr.cannot_reject_approved_advance')], 422);
        }

        $advance->update(['status' => 'rejected']);

        return response()->json(['success' => true, 'message' => __('hr::hr.advance_rejected_successfully')]);
    }

    public function destroy(EmployeeAdvance $advance): JsonResponse
    {
        if ($advance->isApproved()) {
            return response()->json(['success' => false, 'message' => __('hr::hr.cannot_delete_approved_advance')], 422);
        }

        $advance->delete();

        return response()->json(['success' => true, 'message' => __('hr::hr.advance_deleted_successfully')]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAdvance(Request $request): array
    {
        return $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);
    }
}
