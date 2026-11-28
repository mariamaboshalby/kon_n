<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HR\Http\Requests\StoreEmployeeRequest;
use Modules\HR\Models\Employee;
use Modules\HR\Services\EmployeeService;
use Modules\HR\Services\EmployeeStatisticsService;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeService $employeeService
    ) {
        $this->middleware('can:view Employees')->only(['index', 'data', 'show', 'statesByCountry', 'citiesByState', 'townsByCity']);
        $this->middleware('can:create Employees')->only(['create', 'store']);
        $this->middleware('can:edit Employees')->only(['edit', 'update']);
        $this->middleware('can:delete Employees')->only(['destroy']);
    }

    public function index(EmployeeStatisticsService $statisticsService): View
    {
        $lookups = $this->employeeService->formLookups();

        return view('hr::hr-management.employees.index', [
            'employeeStats' => $statisticsService->getDashboardData(),
            'jobs' => $lookups['jobs'],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $paginator = $this->employeeService->listForIndex(
            $request->string('search')->toString() ?: null,
            [
                'job_id' => $request->input('job_id'),
                'date_from' => $request->date('date_from')?->toDateString(),
                'date_to' => $request->date('date_to')?->toDateString(),
            ],
            max(1, min(100, (int) $request->input('per_page', 20)))
        );

        return response()->json([
            'data' => collect($paginator->items())->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'name' => $employee->display_name,
                'nickname' => $employee->name_ar,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'status' => $employee->status,
                'department' => $employee->department?->title,
                'job' => $employee->job?->title,
                'code' => $employee->code,
                'projects' => $employee->relationLoaded('projectsAssigned')
                    ? $employee->projectsAssigned->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'code' => $p->project_code ?? null])->values()
                    : [],
            ]),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function create(): View
    {
        $lookups = $this->employeeService->formLookups();

        return view('hr::hr-management.employees.create', array_merge($lookups, [
            'isEdit' => false,
            'employee' => null,
            'formPayload' => null,
        ]));
    }

    public function store(StoreEmployeeRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $employee = $this->employeeService->create(
                $request->validated(),
                $request->file('image')
            );
        } catch (\Throwable $e) {
            report($e);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() !== '' ? $e->getMessage() : $e::class,
                ], 500);
            }

            throw $e;
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('hr::hr.employee_created_successfully'),
                'redirect' => route('employees.index'),
                'id' => $employee->id,
            ]);
        }

        return redirect()
            ->route('employees.index')
            ->with('success', __('hr::hr.employee_created_successfully'));
    }

    public function show(Employee $employee): View
    {
        $employee = $this->employeeService->findForShow($employee->id);

        return view('hr::hr-management.employees.show', [
            'employee' => $employee,
            'viewEmployee' => $employee,
        ]);
    }

    public function edit(Employee $employee): View
    {
        $employee = $this->employeeService->findForForm($employee->id);
        $lookups = $this->employeeService->formLookups($employee->id);

        return view('hr::hr-management.employees.edit', array_merge($lookups, [
            'isEdit' => true,
            'employee' => $employee,
            'formPayload' => $this->employeeService->formPayload($employee),
        ]));
    }

    public function update(StoreEmployeeRequest $request, Employee $employee): JsonResponse|RedirectResponse
    {
        try {
            $this->employeeService->update(
                $employee,
                $request->validated(),
                $request->file('image')
            );
        } catch (\Throwable $e) {
            report($e);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() !== '' ? $e->getMessage() : $e::class,
                ], 500);
            }

            throw $e;
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('hr::hr.employee_updated_successfully'),
                'redirect' => route('employees.index'),
                'id' => $employee->id,
            ]);
        }

        return redirect()
            ->route('employees.index')
            ->with('success', __('hr::hr.employee_updated_successfully'));
    }

    public function destroy(Request $request, Employee $employee): JsonResponse|RedirectResponse
    {
        $this->employeeService->delete($employee);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('hr::hr.employee_deleted_successfully'),
            ]);
        }

        return redirect()
            ->route('employees.index')
            ->with('success', __('hr::hr.employee_deleted_successfully'));
    }

    public function statesByCountry(int $country): JsonResponse
    {
        return response()->json([
            'data' => $this->employeeService->statesByCountry($country),
        ]);
    }

    public function citiesByState(int $state): JsonResponse
    {
        return response()->json([
            'data' => $this->employeeService->citiesByState($state),
        ]);
    }

    public function townsByCity(int $city): JsonResponse
    {
        return response()->json([
            'data' => $this->employeeService->townsByCity($city),
        ]);
    }
}
