<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HR\Models\EmployeeLeaveBalance;
use Modules\HR\Services\LeaveBalanceService;

class LeaveBalanceController extends Controller
{
    public function __construct(
        private readonly LeaveBalanceService $leaveBalanceService
    ) {
        $this->middleware('can:view Leave Balances')->only(['index', 'data']);
        $this->middleware('can:create Leave Balances')->only(['create', 'store']);
        $this->middleware('can:edit Leave Balances')->only(['edit', 'update']);
        $this->middleware('can:delete Leave Balances')->only(['destroy']);
    }

    public function index(): View
    {
        return view('hr::hr-management.leaves.leave-balances.index', [
            'employees' => $this->leaveBalanceService->employeesForSelect(),
            'leaveTypes' => $this->leaveBalanceService->leaveTypesForSelect(),
            'selectedYear' => now()->year,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->leaveBalanceService->listBalances([
                'search' => $request->string('search')->toString() ?: null,
                'employee_id' => $request->input('employee_id'),
                'leave_type_id' => $request->input('leave_type_id'),
                'year' => $request->input('year'),
            ]),
        ]);
    }

    public function create(): View
    {
        return view('hr::hr-management.leaves.leave-balances.create-edit', [
            'balance' => null,
            'employees' => $this->leaveBalanceService->employeesForSelect(),
            'leaveTypes' => $this->leaveBalanceService->leaveTypesForSelect(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->leaveBalanceService->createBalance($this->validateBalance($request));
        session()->flash('message', __('hr::hr.leave_balance_created_successfully'));

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.leave_balance_created_successfully'),
            'redirect' => route('hr.leaves.balances.index'),
        ]);
    }

    public function edit(EmployeeLeaveBalance $balance): View
    {
        return view('hr::hr-management.leaves.leave-balances.create-edit', [
            'balance' => $balance,
            'employees' => $this->leaveBalanceService->employeesForSelect(),
            'leaveTypes' => $this->leaveBalanceService->leaveTypesForSelect(),
        ]);
    }

    public function update(Request $request, EmployeeLeaveBalance $balance): JsonResponse
    {
        $this->leaveBalanceService->updateBalance($balance, $this->validateBalance($request));
        session()->flash('message', __('hr::hr.leave_balance_updated_successfully'));

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.leave_balance_updated_successfully'),
            'redirect' => route('hr.leaves.balances.index'),
        ]);
    }

    public function destroy(EmployeeLeaveBalance $balance): JsonResponse
    {
        $this->leaveBalanceService->deleteBalance($balance);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.leave_balance_deleted_successfully'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateBalance(Request $request): array
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'year' => 'required|integer|min:2020|max:2030',
            'opening_balance_days' => 'required|numeric|min:0',
            'used_days' => 'required|numeric|min:0',
            'pending_days' => 'required|numeric|min:0',
            'max_monthly_days' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ], [], [
            'employee_id' => __('hr::hr.employee'),
            'leave_type_id' => __('hr::hr.leave_type'),
            'year' => __('hr::hr.year'),
            'opening_balance_days' => __('hr::hr.opening_balance_days'),
            'used_days' => __('hr::hr.used_days'),
            'pending_days' => __('hr::hr.pending_days'),
            'max_monthly_days' => __('hr::hr.max_monthly_days'),
            'notes' => __('hr::hr.notes'),
        ]);

        $validated['notes'] = isset($validated['notes']) && $validated['notes'] !== ''
            ? $validated['notes']
            : null;

        return $validated;
    }
}
