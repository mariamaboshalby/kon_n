<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\HR\Models\Contract;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Termination;

class TerminationController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view Terminations')->only(['index', 'data', 'show']);
        $this->middleware('can:create Terminations')->only(['store']);
        $this->middleware('can:edit Terminations')->only(['update']);
        $this->middleware('can:delete Terminations')->only(['destroy']);
    }

    public function index(): View
    {
        $employees = Employee::query()->orderBy('name')->get(['id', 'name']);
        $contracts = Contract::query()->latest()->get(['id', 'name', 'employee_id']);

        return view('recruitment::terminations.manage', compact('employees', 'contracts'));
    }

    public function data(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $type = $request->string('type')->toString();
        $employeeId = $request->input('employee_id');
        $contractId = $request->input('contract_id');
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();

        $rows = Termination::query()
            ->with(['employee:id,name', 'contract:id,name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('employee', fn ($q) => $q->where('name', 'like', '%'.$search.'%'));
            })
            ->when($type !== '', fn ($q) => $q->where('termination_type', $type))
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->when($contractId, fn ($q) => $q->where('contract_id', $contractId))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('termination_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('termination_date', '<=', $dateTo))
            ->latest('termination_date')
            ->get()
            ->map(fn (Termination $row) => [
                'id' => $row->id,
                'employee_id' => $row->employee_id,
                'employee_name' => $row->employee?->name,
                'contract_id' => $row->contract_id,
                'contract_name' => $row->contract?->name,
                'termination_type' => $row->termination_type,
                'termination_date' => $row->termination_date?->format('Y-m-d'),
                'reason' => $row->reason,
                'final_settlement' => $row->final_settlement,
            ]);

        return response()->json(['data' => $rows]);
    }

    public function show($id): View
    {
        $termination = Termination::with(['contract', 'branch', 'employee'])->findOrFail($id);

        return view('recruitment::terminations.show', compact('termination'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validated($request);
        $validated['created_by'] = Auth::id();
        $validated['branch_id'] = $this->resolveBranchId();

        Termination::create($validated);

        return response()->json([
            'success' => true,
            'message' => __('recruitment.termination_created_successfully'),
        ]);
    }

    public function update(Request $request, Termination $termination): JsonResponse
    {
        $termination->update($this->validated($request));

        return response()->json([
            'success' => true,
            'message' => __('recruitment.termination_updated_successfully'),
        ]);
    }

    public function destroy(Termination $termination): JsonResponse
    {
        $termination->delete();

        return response()->json([
            'success' => true,
            'message' => __('recruitment.termination_deleted_successfully'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'contract_id' => 'nullable|exists:contracts,id',
            'termination_type' => 'required|in:resignation,dismissal,death,retirement',
            'termination_date' => 'required|date',
            'reason' => 'nullable|string',
            'final_settlement' => 'nullable|numeric|min:0',
        ]);
    }

    private function resolveBranchId(): ?int
    {
        $branchId = optional(Auth::user())
            ->branches()
            ->where('branches.is_active', 1)
            ->value('branches.id');

        return $branchId !== null ? (int) $branchId : null;
    }
}
