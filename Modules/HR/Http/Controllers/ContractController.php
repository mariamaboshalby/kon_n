<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\HR\Models\Contract;
use Modules\HR\Models\ContractType;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeesJob;

class ContractController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view Contracts')->only(['index', 'data', 'show']);
        $this->middleware('can:create Contracts')->only(['store']);
        $this->middleware('can:edit Contracts')->only(['update']);
        $this->middleware('can:delete Contracts')->only(['destroy']);
    }

    public function index(): View
    {
        $contractTypes = ContractType::query()->orderBy('name')->get(['id', 'name']);
        $jobs = EmployeesJob::query()->orderBy('title')->get(['id', 'title']);
        $employees = Employee::query()->orderBy('name')->get(['id', 'name']);

        return view('recruitment::contracts.manage', compact('contractTypes', 'jobs', 'employees'));
    }

    public function data(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $contractTypeId = $request->input('contract_type_id');
        $jobId = $request->input('job_id');
        $employeeId = $request->input('employee_id');
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();

        $rows = Contract::query()
            ->with(['employee:id,name', 'contract_type:id,name', 'job:id,title', 'contract_points', 'salary_points'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhereHas('employee', fn ($e) => $e->where('name', 'like', '%'.$search.'%'));
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($contractTypeId, fn ($q) => $q->where('contract_type_id', $contractTypeId))
            ->when($jobId, fn ($q) => $q->where('job_id', $jobId))
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('contract_start_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('contract_start_date', '<=', $dateTo))
            ->latest()
            ->get()
            ->map(fn (Contract $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'contract_type_id' => $c->contract_type_id,
                'contract_type_name' => $c->contract_type?->name,
                'employee_id' => $c->employee_id,
                'employee_name' => $c->employee?->name,
                'job_id' => $c->job_id,
                'job_title' => $c->job?->title,
                'contract_start_date' => $c->contract_start_date,
                'contract_end_date' => $c->contract_end_date,
                'trial_period' => $c->trial_period !== null ? (int) $c->trial_period : null,
                'status' => $c->status,
                'contract_duration' => $c->contract_duration,
                'fixed_work_hours' => $c->fixed_work_hours,
                'additional_work_hours' => $c->additional_work_hours,
                'monthly_holidays' => $c->monthly_holidays,
                'monthly_sick_days' => $c->monthly_sick_days,
                'information' => $c->information,
                'job_description' => $c->job_description,
                'contract_points' => $c->contract_points->map(fn ($p) => [
                    'name' => $p->name,
                    'information' => $p->information,
                    'sequence' => $p->sequence,
                ])->values(),
                'salary_points' => $c->salary_points->map(fn ($p) => [
                    'name' => $p->name,
                    'information' => $p->information,
                    'sequence' => $p->sequence,
                ])->values(),
            ]);

        return response()->json(['data' => $rows]);
    }

    public function show($id): View
    {
        $contract = Contract::with([
            'branch', 'contract_type', 'job', 'employee', 'user',
            'contract_points', 'salary_points', 'cv', 'interview',
        ])->findOrFail($id);

        return view('recruitment::contracts.show', compact('contract'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validated($request);

        DB::transaction(function () use ($validated) {
            $points = $validated['contract_points'] ?? [];
            $salary = $validated['salary_points'] ?? [];
            unset($validated['contract_points'], $validated['salary_points']);

            $validated['created_by'] = Auth::id();
            $validated['branch_id'] = $this->resolveBranchId();
            $contract = Contract::create($validated);
            $this->syncPoints($contract, $points, $salary);
        });

        return response()->json([
            'success' => true,
            'message' => __('recruitment.contract_created_successfully'),
        ]);
    }

    public function update(Request $request, Contract $contract): JsonResponse
    {
        $validated = $this->validated($request);

        DB::transaction(function () use ($validated, $contract) {
            $points = $validated['contract_points'] ?? [];
            $salary = $validated['salary_points'] ?? [];
            unset($validated['contract_points'], $validated['salary_points']);

            $contract->update($validated);
            $this->syncPoints($contract, $points, $salary);
        });

        return response()->json([
            'success' => true,
            'message' => __('recruitment.contract_updated_successfully'),
        ]);
    }

    public function destroy(Contract $contract): JsonResponse
    {
        DB::transaction(function () use ($contract) {
            $contract->contract_points()->delete();
            $contract->salary_points()->delete();
            $contract->delete();
        });

        return response()->json([
            'success' => true,
            'message' => __('recruitment.contract_deleted_successfully'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'contract_type_id' => 'required|exists:contract_types,id',
            'employee_id' => 'required|exists:employees,id',
            'job_id' => 'required|exists:employees_jobs,id',
            'contract_start_date' => 'required|date',
            'contract_end_date' => 'required|date|after_or_equal:contract_start_date',
            'trial_period' => 'nullable|integer|min:0',
            'status' => 'required|in:pending,active,expired,terminated',
            'fixed_work_hours' => 'nullable|numeric|min:0',
            'additional_work_hours' => 'nullable|numeric|min:0',
            'monthly_holidays' => 'nullable|numeric|min:0',
            'monthly_sick_days' => 'nullable|numeric|min:0',
            'information' => 'nullable|string',
            'job_description' => 'nullable|string',
            'contract_points' => 'nullable|array',
            'contract_points.*.name' => 'required|string|max:255',
            'contract_points.*.information' => 'nullable|string',
            'salary_points' => 'nullable|array',
            'salary_points.*.name' => 'required|string|max:255',
            'salary_points.*.information' => 'nullable|string',
        ]);
    }

    /**
     * @param  list<array{name:string,information?:string,sequence?:int}>  $points
     * @param  list<array{name:string,information?:string,sequence?:int}>  $salary
     */
    private function syncPoints(Contract $contract, array $points, array $salary): void
    {
        $contract->contract_points()->delete();
        foreach ($points as $index => $point) {
            if (empty($point['name'])) {
                continue;
            }
            $contract->contract_points()->create([
                'name' => $point['name'],
                'information' => $point['information'] ?? '',
                'sequence' => $point['sequence'] ?? ($index + 1),
            ]);
        }

        $contract->salary_points()->delete();
        foreach ($salary as $index => $point) {
            if (empty($point['name'])) {
                continue;
            }
            $contract->salary_points()->create([
                'name' => $point['name'],
                'information' => $point['information'] ?? '',
                'sequence' => $point['sequence'] ?? ($index + 1),
            ]);
        }
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
