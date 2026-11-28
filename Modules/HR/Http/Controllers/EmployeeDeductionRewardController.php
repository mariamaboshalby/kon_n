<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\HR\Models\Department;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeeDeductionReward;
use Modules\HR\Services\EmployeeDeductionRewardService;

class EmployeeDeductionRewardController extends Controller
{
    public function __construct(
        private readonly EmployeeDeductionRewardService $deductionRewardService
    ) {
        $this->middleware('can:view Employee Deductions')->only(['index', 'data']);
        $this->middleware('can:create Employee Deductions')->only(['store']);
        $this->middleware('can:edit Employee Deductions')->only(['update', 'approve']);
        $this->middleware('can:delete Employee Deductions')->only(['destroy']);
    }

    public function index(): View
    {
        return view('hr::hr-management.employee-deductions-rewards.index', [
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = EmployeeDeductionReward::query()
            ->with('employee:id,name');

        if ($request->filled('department_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department_id', (int) $request->input('department_id'));
            });
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', (int) $request->input('employee_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->input('end_date'));
        }

        $items = (clone $query)->orderByDesc('created_at')->limit(300)->get();

        $totalDeductions = (float) $items->where('type', 'deduction')->sum('amount');
        $totalRewards = (float) $items->where('type', 'reward')->sum('amount');

        $summary = [
            'total_count' => $items->count(),
            'total_deductions' => $totalDeductions,
            'total_rewards' => $totalRewards,
            'net_total' => $totalRewards - $totalDeductions,
        ];

        return response()->json([
            'data' => $items,
            'summary' => $summary,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateItem($request);
        $data['created_by'] = Auth::id();
        EmployeeDeductionReward::create($data);

        return response()->json(['success' => true, 'message' => __('hr::hr.deduction_reward_created_successfully')]);
    }

    public function update(Request $request, EmployeeDeductionReward $item): JsonResponse
    {
        if ($item->hasJournal()) {
            return response()->json(['success' => false, 'message' => __('hr::hr.cannot_edit_journal_linked_item')], 422);
        }

        $data = $this->validateItem($request);
        $data['updated_by'] = Auth::id();
        $item->update($data);

        return response()->json(['success' => true, 'message' => __('hr::hr.deduction_reward_updated_successfully')]);
    }

    public function approve(EmployeeDeductionReward $item): JsonResponse
    {
        if ($item->hasJournal()) {
            return response()->json(['success' => false, 'message' => __('hr::hr.already_approved')], 422);
        }

        try {
            DB::transaction(function () use ($item) {
                if ($item->isDeduction()) {
                    $this->deductionRewardService->createDeductionJournal($item);
                } else {
                    $this->deductionRewardService->createRewardJournal($item);
                }
            });
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => __('hr::hr.deduction_reward_approved_successfully')]);
    }

    public function destroy(EmployeeDeductionReward $item): JsonResponse
    {
        if ($item->hasJournal()) {
            return response()->json(['success' => false, 'message' => __('hr::hr.cannot_delete_journal_linked_item')], 422);
        }

        $item->delete();

        return response()->json(['success' => true, 'message' => __('hr::hr.deduction_reward_deleted_successfully')]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateItem(Request $request): array
    {
        return $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|in:deduction,reward',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);
    }
}
