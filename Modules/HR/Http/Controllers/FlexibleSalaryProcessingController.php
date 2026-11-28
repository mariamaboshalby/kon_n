<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use App\Services\JournalService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Accounts\Models\AccHead;
use Modules\HR\Models\Department;
use Modules\HR\Models\Employee;
use Modules\HR\Models\FlexibleSalaryProcessing;

class FlexibleSalaryProcessingController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view Flexible Salary Processing')->only(['index', 'data']);
        $this->middleware('can:create Flexible Salary Processing')->only(['create', 'store', 'employees']);
        $this->middleware('can:edit Flexible Salary Processing')->only(['edit', 'update', 'approve', 'reject']);
    }

    public function index(): View
    {
        return view('hr::hr-management.attendances.flexible-salary-processing.index', [
            'departments' => Department::query()->orderBy('title')->get(['id', 'title']),
            'employees' => Employee::query()
                ->where('salary_type', 'ثابت + ساعات عمل مرن')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = FlexibleSalaryProcessing::query()
            ->with(['employee:id,name', 'department:id,title']);

        if ($request->filled('start_date')) {
            $query->whereDate('period_start', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('period_end', '<=', $request->input('end_date'));
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', (int) $request->input('department_id'));
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', (int) $request->input('employee_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereHas('employee', function ($eq) use ($search) {
                    $eq->where('name', 'like', "%{$search}%");
                })->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $items = (clone $query)->orderByDesc('created_at')->limit(300)->get();

        $summary = [
            'total_count' => $items->count(),
            'total_hours' => (float) $items->sum('hours_worked'),
            'total_fixed_salary' => (float) $items->sum('fixed_salary'),
            'total_salary' => (float) $items->sum('total_salary'),
        ];

        return response()->json([
            'data' => $items,
            'summary' => $summary,
        ]);
    }

    public function create(): View
    {
        return view('hr::hr-management.attendances.flexible-salary-processing.create', [
            'employees' => $this->flexibleEmployees(),
            'departments' => Department::query()->orderBy('title')->get(['id', 'title']),
            'startDate' => now()->startOfMonth()->format('Y-m-d'),
            'endDate' => now()->endOfMonth()->format('Y-m-d'),
        ]);
    }

    public function employees(Request $request): JsonResponse
    {
        $query = Employee::query()
            ->where('salary_type', 'ثابت + ساعات عمل مرن')
            ->orderBy('name');
        if ($request->integer('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }

        return response()->json([
            'data' => $query->get(['id', 'name', 'salary', 'flexible_hourly_wage', 'department_id']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'processing_type' => 'required|in:single,department',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'selected_employee' => 'required_if:processing_type,single|nullable|exists:employees,id',
            'selected_department' => 'required_if:processing_type,department|nullable|exists:departments,id',
            'employee_hours' => 'required|array',
            'employee_hours.*' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $hoursMap = $validated['employee_hours'];
        $results = [];
        $errors = [];

        $employees = $validated['processing_type'] === 'single'
            ? Employee::query()->whereKey((int) $validated['selected_employee'])->get()
            : Employee::query()
                ->where('department_id', (int) $validated['selected_department'])
                ->where('salary_type', 'ثابت + ساعات عمل مرن')
                ->where('status', 'مفعل')
                ->get();

        foreach ($employees as $employee) {
            $hours = (float) ($hoursMap[$employee->id] ?? 0);
            if ($hours <= 0) {
                continue;
            }
            try {
                $results[] = $this->processEmployeeSalary($employee, $startDate, $endDate, $hours, $validated['notes'] ?? null);
            } catch (\Throwable $e) {
                $errors[] = $employee->name.': '.$e->getMessage();
            }
        }

        if ($results === [] && $errors === []) {
            return response()->json(['success' => false, 'message' => __('hr::hr.no_employees_processed')], 422);
        }

        return response()->json([
            'success' => $errors === [],
            'message' => $errors === []
                ? __('hr::hr.flexible_salary_processed_successfully', ['count' => count($results)])
                : __('hr::hr.flexible_salary_processed_with_errors'),
            'errors' => $errors,
            'redirect' => route('hr.flexible-salary.processing.index'),
        ], $errors === [] ? 200 : 422);
    }

    public function edit(FlexibleSalaryProcessing $processing): View
    {
        abort_unless($processing->status === 'pending', 403);

        return view('hr::hr-management.attendances.flexible-salary-processing.edit', [
            'processing' => $processing->load('employee'),
        ]);
    }

    public function update(Request $request, FlexibleSalaryProcessing $processing): JsonResponse
    {
        if ($processing->status !== 'pending') {
            return response()->json(['success' => false, 'message' => __('hr::hr.can_only_edit_pending_processing')], 422);
        }

        $validated = $request->validate([
            'hours_worked' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        $employee = $processing->employee;
        $hourlyWage = (float) ($employee->flexible_hourly_wage ?? 0);
        $totalSalary = (float) ($employee->salary ?? 0) + ((float) $validated['hours_worked'] * $hourlyWage);

        $processing->update([
            'hours_worked' => $validated['hours_worked'],
            'total_salary' => $totalSalary,
            'notes' => $validated['notes'] ?? null,
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.processing_updated_successfully'),
            'redirect' => route('hr.flexible-salary.processing.index'),
        ]);
    }

    public function approve(FlexibleSalaryProcessing $processing): JsonResponse
    {
        if ($processing->status === 'approved') {
            return response()->json(['success' => false, 'message' => __('hr::hr.processing_already_approved')], 422);
        }

        try {
            DB::transaction(function () use ($processing) {
                $employee = $processing->employee;
                if (! $employee?->account) {
                    throw new \RuntimeException(__('hr::hr.employee_main_account_missing'));
                }
                $debitAccount = AccHead::where('code', '5301')->first();
                if (! $debitAccount) {
                    throw new \RuntimeException(__('hr::hr.salary_expense_account_missing'));
                }

                $journalId = app(JournalService::class)->createJournal([
                    [
                        'account_id' => $debitAccount->id,
                        'debit' => $processing->total_salary,
                        'credit' => 0,
                        'type' => 1,
                        'info' => "راتب مرن للموظف: {$employee->name} - معالجة #{$processing->id}",
                    ],
                    [
                        'account_id' => $employee->account->id,
                        'debit' => 0,
                        'credit' => $processing->total_salary,
                        'type' => 0,
                        'info' => "راتب مرن للموظف: {$employee->name} - معالجة #{$processing->id}",
                    ],
                ], [
                    'pro_type' => 77,
                    'date' => now(),
                    'info' => "راتب مرن للموظف: {$employee->name}",
                    'details' => "معالجة الراتب المرن #{$processing->id} - الفترة من {$processing->period_start->format('Y-m-d')} إلى {$processing->period_end->format('Y-m-d')}",
                    'emp_id' => $employee->id,
                ]);

                $processing->status = 'approved';
                $processing->journal_id = $journalId;
                $processing->save();
            });
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => __('hr::hr.processing_approved_successfully')]);
    }

    public function reject(FlexibleSalaryProcessing $processing): JsonResponse
    {
        $processing->update(['status' => 'rejected']);

        return response()->json(['success' => true, 'message' => __('hr::hr.processing_rejected_successfully')]);
    }

    /**
     * @return array<string, mixed>
     */
    private function processEmployeeSalary(Employee $employee, Carbon $startDate, Carbon $endDate, float $hoursWorked, ?string $notes): array
    {
        if ($employee->salary_type !== 'ثابت + ساعات عمل مرن') {
            throw new \RuntimeException(__('hr::hr.employee_not_flexible_salary'));
        }

        return DB::transaction(function () use ($employee, $startDate, $endDate, $hoursWorked, $notes) {
            $existing = FlexibleSalaryProcessing::query()
                ->where('employee_id', $employee->id)
                ->where('period_start', $startDate->format('Y-m-d'))
                ->where('period_end', $endDate->format('Y-m-d'))
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->status === 'rejected') {
                    $existing->delete();
                } else {
                    throw new \RuntimeException(__('hr::hr.flexible_salary_already_processed'));
                }
            }

            $fixedSalary = (float) ($employee->salary ?? 0);
            $hourlyWage = (float) ($employee->flexible_hourly_wage ?? 0);
            $processing = FlexibleSalaryProcessing::create([
                'employee_id' => $employee->id,
                'department_id' => $employee->department_id,
                'period_start' => $startDate->format('Y-m-d'),
                'period_end' => $endDate->format('Y-m-d'),
                'fixed_salary' => $fixedSalary,
                'hours_worked' => $hoursWorked,
                'hourly_wage' => $hourlyWage,
                'total_salary' => $fixedSalary + ($hoursWorked * $hourlyWage),
                'status' => 'pending',
                'notes' => $notes,
                'created_by' => Auth::id(),
            ]);

            return [
                'processing_id' => $processing->id,
                'employee_name' => $employee->name,
            ];
        });
    }

    private function flexibleEmployees()
    {
        return Employee::query()
            ->where('salary_type', 'ثابت + ساعات عمل مرن')
            ->orderBy('name')
            ->get(['id', 'name', 'salary', 'flexible_hourly_wage', 'department_id']);
    }
}
