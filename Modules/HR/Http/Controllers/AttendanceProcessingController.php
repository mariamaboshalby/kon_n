<?php

declare(strict_types=1);

namespace Modules\HR\Http\Controllers;

use App\Services\JournalService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Accounts\Models\AccHead;
use Modules\HR\Models\AttendanceProcessing;
use Modules\HR\Models\Department;
use Modules\HR\Models\Employee;
use Modules\HR\Services\AttendanceProcessingService;

class AttendanceProcessingController extends Controller
{
    public function __construct(
        private readonly AttendanceProcessingService $attendanceProcessingService
    ) {
        $this->middleware('can:view Attendance Processing')->only(['index', 'data', 'show']);
        $this->middleware('can:create Attendance Processing')->only(['process']);
        $this->middleware('can:edit Attendance Processing')->only(['approve', 'reject']);
        $this->middleware('can:delete Attendance Processing')->only(['destroy']);
    }

    public function index(): View
    {
        return view('hr::hr-management.attendances.processing.manage-processing', [
            'employees' => Employee::query()
                ->where('status', '!=', 'خارج الشركة')
                ->orderBy('name')
                ->get(['id', 'name', 'department_id']),
            'departments' => Department::query()->orderBy('title')->get(['id', 'title']),
            'startDate' => now()->startOfMonth()->format('Y-m-d'),
            'endDate' => now()->endOfMonth()->format('Y-m-d'),
        ]);
    }

    public function data(): JsonResponse
    {
        $items = AttendanceProcessing::query()
            ->with(['employee:id,name', 'department:id,title'])
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        return response()->json(['data' => $items]);
    }

    public function show(AttendanceProcessing $processing): JsonResponse
    {
        return response()->json(
            $this->attendanceProcessingService->getProcessingDetails($processing->id)
        );
    }

    public function process(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'processing_type' => 'required|in:single,multiple,department',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'selected_employee' => 'required_if:processing_type,single|nullable|exists:employees,id',
            'selected_department' => 'required_if:processing_type,department|nullable|exists:departments,id',
            'selected_employees' => 'required_if:processing_type,multiple|nullable|array',
            'selected_employees.*' => 'exists:employees,id',
            'notes' => 'nullable|string',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $notes = $validated['notes'] ?? null;

        try {
            $results = match ($validated['processing_type']) {
                'single' => $this->attendanceProcessingService->processSingleEmployee(
                    Employee::findOrFail((int) $validated['selected_employee']),
                    $startDate,
                    $endDate,
                    $notes
                ),
                'multiple' => $this->attendanceProcessingService->processMultipleEmployees(
                    array_map('intval', $validated['selected_employees'] ?? []),
                    $startDate,
                    $endDate,
                    $notes
                ),
                'department' => $this->attendanceProcessingService->processDepartment(
                    Department::findOrFail((int) $validated['selected_department']),
                    $startDate,
                    $endDate,
                    $notes
                ),
            };
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        if (isset($results['error'])) {
            return response()->json([
                'success' => false,
                'message' => $results['error'],
            ], 422);
        }

        if (isset($results['results'])) {
            $errors = collect($results['results'])->pluck('error')->filter()->values();
            if ($errors->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => $errors->implode("\n"),
                ], 422);
            }
        }

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.attendance_processed_successfully'),
            'results' => $results,
        ]);
    }

    public function approve(AttendanceProcessing $processing): JsonResponse
    {
        if ($processing->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => __('hr::hr.processing_already_approved'),
            ], 422);
        }

        try {
            DB::transaction(function () use ($processing) {
                $this->createApprovalJournal($processing);
                $processing->status = 'approved';
                $processing->save();
            });
        } catch (\Throwable $e) {
            Log::error('Error approving processing: '.$e->getMessage(), [
                'processing_id' => $processing->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => __('hr::hr.error_approving_processing', ['error' => $e->getMessage()]),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.processing_approved_successfully'),
        ]);
    }

    public function reject(AttendanceProcessing $processing): JsonResponse
    {
        $processing->update(['status' => 'rejected']);

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.processing_rejected_successfully'),
        ]);
    }

    public function destroy(AttendanceProcessing $processing): JsonResponse
    {
        if ($processing->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => __('hr::hr.cannot_delete_approved_processing'),
            ], 422);
        }

        $processing->delete();

        return response()->json([
            'success' => true,
            'message' => __('hr::hr.processing_deleted_successfully'),
        ]);
    }

    private function createApprovalJournal(AttendanceProcessing $processing): void
    {
        $employee = Employee::with('account')->findOrFail($processing->employee_id);
        if (! $employee->account) {
            throw new \RuntimeException(__('hr::hr.employee_main_account_missing'));
        }

        $salaryExpenseAccount = AccHead::where('code', '5301')->first();
        if (! $salaryExpenseAccount) {
            throw new \RuntimeException(__('hr::hr.salary_expense_account_missing'));
        }

        $overtimeAccount = AccHead::where('accountable_type', Employee::class)
            ->where('accountable_id', $employee->id)
            ->where('aname', 'like', 'إضافى حضور%')
            ->first();

        $deductionAccount = AccHead::where('accountable_type', Employee::class)
            ->where('accountable_id', $employee->id)
            ->where('aname', 'like', 'خصم حضور%')
            ->first();

        $basicSalary = $processing->salary_due ?? 0;
        $overtimeAmount = 0;
        if ($processing->overtime_work_minutes > 0 && $processing->calculated_salary_for_hour > 0) {
            $overtimeHours = $processing->overtime_work_minutes / 60;
            $overtimeMultiplier = $employee->additional_hour_calculation ?? 1.5;
            $overtimeAmount = $overtimeHours * $processing->calculated_salary_for_hour * $overtimeMultiplier;
        }

        $deductionAmount = 0;
        if ($processing->total_late_minutes > 0 && $processing->calculated_salary_for_hour > 0) {
            $lateHourCalculation = $employee->late_hour_calculation ?? 1.0;
            $deductionAmount += ($processing->total_late_minutes / 60) * $lateHourCalculation * $processing->calculated_salary_for_hour;
        }
        if ($processing->absent_days > 0 && $processing->calculated_salary_for_day > 0) {
            $lateDayCalculation = $employee->late_day_calculation ?? 1.0;
            $deductionAmount += $processing->absent_days * $lateDayCalculation * $processing->calculated_salary_for_day;
        }
        if ($processing->unpaid_leave_days > 0 && $processing->calculated_salary_for_day > 0) {
            $deductionAmount += $processing->unpaid_leave_days * $processing->calculated_salary_for_day;
        }

        $netSalary = $basicSalary + $overtimeAmount - $deductionAmount;
        if ($basicSalary <= 0 && $overtimeAmount <= 0 && $deductionAmount <= 0) {
            return;
        }

        $lines = [];
        if ($basicSalary > 0) {
            $lines[] = [
                'account_id' => $salaryExpenseAccount->id,
                'debit' => $basicSalary,
                'credit' => 0,
                'type' => 1,
                'info' => "راتب الموظف: {$employee->name} - معالجة #{$processing->id}",
            ];
        }
        if ($overtimeAmount > 0 && $overtimeAccount) {
            $lines[] = [
                'account_id' => $overtimeAccount->id,
                'debit' => round($overtimeAmount, 2),
                'credit' => 0,
                'type' => 1,
                'info' => "إضافي حضور: {$employee->name} - معالجة #{$processing->id}",
            ];
        }
        if ($deductionAmount > 0 && $deductionAccount) {
            $lines[] = [
                'account_id' => $deductionAccount->id,
                'debit' => 0,
                'credit' => round($deductionAmount, 2),
                'type' => 0,
                'info' => "خصم حضور: {$employee->name} - معالجة #{$processing->id}",
            ];
        }
        if ($netSalary != 0) {
            $lines[] = [
                'account_id' => $employee->account->id,
                'debit' => $netSalary < 0 ? abs($netSalary) : 0,
                'credit' => $netSalary > 0 ? $netSalary : 0,
                'type' => $netSalary > 0 ? 0 : 1,
                'info' => "راتب الموظف: {$employee->name} - معالجة #{$processing->id}",
            ];
        }

        app(JournalService::class)->createJournal($lines, [
            'pro_type' => 74,
            'date' => now(),
            'info' => "راتب الموظف: {$employee->name}",
            'details' => "معالجة البصمات #{$processing->id} - الفترة من {$processing->period_start->format('Y-m-d')} إلى {$processing->period_end->format('Y-m-d')}",
            'emp_id' => $employee->id,
        ]);
    }
}
