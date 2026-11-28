<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use App\Services\JournalService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Models\AccHead;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeeAdvance;
use RuntimeException;

class EmployeeAdvanceApprovalService
{
    /**
     * اعتماد السلفة + قيد (سلف الموظف مدين / الصندوق دائن).
     *
     * @throws RuntimeException
     */
    public function approve(EmployeeAdvance $advance): void
    {
        if ($advance->isApproved()) {
            throw new RuntimeException(__('hr::hr.advance_already_approved'));
        }

        DB::transaction(function () use ($advance): void {
            $employee = $advance->employee;
            $employeeAdvanceAccount = AccHead::where('accountable_type', Employee::class)
                ->where('accountable_id', $employee->id)
                ->where('aname', 'like', '%سلف%')
                ->first();
            if (! $employeeAdvanceAccount) {
                throw new RuntimeException(__('hr::hr.employee_advance_account_missing'));
            }
            $cashAccount = AccHead::where('code', '110101')->first();
            if (! $cashAccount) {
                throw new RuntimeException(__('hr::hr.cash_account_missing'));
            }

            $journalId = app(JournalService::class)->createJournal([
                [
                    'account_id' => $employeeAdvanceAccount->id,
                    'debit' => $advance->amount,
                    'credit' => 0,
                    'type' => 1,
                    'info' => "سلف للموظف: {$employee->name} - {$advance->reason}",
                ],
                [
                    'account_id' => $cashAccount->id,
                    'debit' => 0,
                    'credit' => $advance->amount,
                    'type' => 0,
                    'info' => "سلف للموظف: {$employee->name} - {$advance->reason}",
                ],
            ], [
                'pro_type' => 78,
                'date' => $advance->date,
                'info' => "سلف للموظف: {$employee->name}",
                'details' => "سلف: {$advance->reason}",
                'emp_id' => $employee->id,
            ]);

            $advance->status = 'approved';
            $advance->journal_id = $journalId;
            $advance->approved_by = Auth::id();
            $advance->approved_at = now();
            $advance->save();
        });
    }

    /**
     * @throws RuntimeException
     */
    public function reject(EmployeeAdvance $advance): void
    {
        if ($advance->isApproved()) {
            throw new RuntimeException(__('hr::hr.cannot_reject_approved_advance'));
        }

        $advance->update(['status' => 'rejected']);
    }
}
