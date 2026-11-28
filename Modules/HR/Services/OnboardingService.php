<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Modules\Accounts\Models\AccHead;
use Modules\Accounts\Services\AccountService;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Onboarding;
use RuntimeException;

final class OnboardingService
{
    public function __construct(
        private readonly AccountService $accountService,
    ) {}

    /**
     * Create employee from CV when onboarding becomes completed (checklist must be done).
     *
     * @return array{ok: bool, message?: string, employee?: Employee}
     */
    public function createEmployeeIfNeeded(Onboarding $onboarding): array
    {
        if ($onboarding->employee_id) {
            return ['ok' => true];
        }

        if (! empty($onboarding->checklist)) {
            foreach ($onboarding->checklist as $item) {
                if (empty($item['completed'])) {
                    return [
                        'ok' => false,
                        'message' => __('recruitment.cannot_create_employee_checklist_incomplete'),
                    ];
                }
            }
        }

        $cv = $onboarding->cv;
        if (! $cv) {
            return [
                'ok' => false,
                'message' => __('recruitment.cv_required_to_create_employee'),
            ];
        }

        $salaryBasicAccount = AccHead::where([
            'acc_type' => 5,
            'is_basic' => 1,
        ])->first();

        if (! $salaryBasicAccount) {
            return [
                'ok' => false,
                'message' => __('recruitment.salary_basic_account_not_found'),
            ];
        }

        $maritalStatusMap = [
            'single' => 'غير متزوج',
            'married' => 'متزوج',
            'divorced' => 'مطلق',
            'widowed' => 'أرمل',
        ];
        $maritalStatus = $cv->marital_status;
        if (isset($maritalStatusMap[$maritalStatus])) {
            $maritalStatus = $maritalStatusMap[$maritalStatus];
        }

        try {
            $employee = Employee::create([
                'name' => $cv->name,
                'email' => $cv->email ?: (strtolower(str_replace(' ', '.', $cv->name)).'@company.com'),
                'phone' => $cv->phone ?? '123456789',
                'password' => Hash::make('123456'),
                'status' => 'مفعل',
                'gender' => $cv->gender,
                'date_of_birth' => $cv->birth_date ? date('Y-m-d', strtotime((string) $cv->birth_date)) : null,
                'marital_status' => $maritalStatus,
                'date_of_hire' => $onboarding->completion_date ?? now(),
                'branch_id' => $onboarding->branch_id,
            ]);

            $onboarding->update(['employee_id' => $employee->id]);
            $this->syncEmployeeAccount($employee, (int) $salaryBasicAccount->id, 0);

            return [
                'ok' => true,
                'employee' => $employee,
                'message' => __('recruitment.employee_created_successfully', ['name' => $employee->name]),
            ];
        } catch (\Throwable $e) {
            Log::error('Onboarding employee creation failed', ['error' => $e->getMessage()]);

            return [
                'ok' => false,
                'message' => __('recruitment.employee_creation_failed').': '.$e->getMessage(),
            ];
        }
    }

    private function syncEmployeeAccount(Employee $employee, int $parentAccountId, float $openingBalance = 0): void
    {
        $employee->load('account');
        $parentAccount = AccHead::where('id', $parentAccountId)->first();
        if (! $parentAccount) {
            throw new RuntimeException('Parent account not found');
        }

        $children = $parentAccount->haveChildrens()->get();
        $maxChildCode = null;
        foreach ($children as $child) {
            if (is_numeric($child->code)) {
                $childCodeValue = (int) $child->code;
                if ($maxChildCode === null || $childCodeValue > $maxChildCode) {
                    $maxChildCode = $childCodeValue;
                }
            }
        }

        if ($maxChildCode !== null) {
            $newCode = (string) ($maxChildCode + 1);
        } else {
            $newCodeValue = (int) $parentAccount->code + 1;
            while (AccHead::where('code', (string) $newCodeValue)->exists()) {
                $newCodeValue++;
            }
            $newCode = (string) $newCodeValue;
        }

        $accountData = [
            'code' => $newCode,
            'aname' => $employee->name,
            'parent_id' => $parentAccountId,
            'acc_type' => 5,
            'accountable_type' => Employee::class,
            'accountable_id' => $employee->id,
        ];

        if (! $employee->account) {
            $employee->account()->create($accountData);
            $employee->load('account');
            $this->accountService->setStartBalances([$employee->account->id => $openingBalance]);
            $this->accountService->recalculateOpeningCapitalAndSyncJournal();
        } else {
            unset($accountData['accountable_type'], $accountData['accountable_id']);
            $employee->account->update($accountData);
            $this->accountService->setStartBalances([$employee->account->id => $openingBalance]);
            $this->accountService->recalculateOpeningCapitalAndSyncJournal();
        }

        $this->createEmployeeSubAccounts($employee);
    }

    private function createEmployeeSubAccounts(Employee $employee): void
    {
        $this->createOrUpdateSubAccount($employee, '110601', 'سلف '.$employee->name);
        $this->createOrUpdateSubAccount($employee, '210402', 'جزاءات وخصومات '.$employee->name);
        $this->createOrUpdateSubAccount($employee, '5303', 'مكافآت وحوافز '.$employee->name);
    }

    private function createOrUpdateSubAccount(Employee $employee, string $parentCode, string $accountName): void
    {
        $parentAccount = AccHead::where('code', $parentCode)->first();
        if (! $parentAccount) {
            Log::warning("Parent account with code {$parentCode} not found for employee {$employee->id}");

            return;
        }

        $existingAccount = AccHead::where('accountable_type', Employee::class)
            ->where('accountable_id', $employee->id)
            ->where('aname', $accountName)
            ->where('parent_id', $parentAccount->id)
            ->first();

        if ($existingAccount) {
            $existingAccount->update([
                'aname' => $accountName,
                'mdtime' => now(),
            ]);

            return;
        }

        $children = $parentAccount->haveChildrens()->get();
        $maxChildCode = null;
        foreach ($children as $child) {
            if (is_numeric($child->code)) {
                $childCodeValue = (int) $child->code;
                if ($maxChildCode === null || $childCodeValue > $maxChildCode) {
                    $maxChildCode = $childCodeValue;
                }
            }
        }

        if ($maxChildCode !== null) {
            $newCode = (string) ($maxChildCode + 1);
        } else {
            $newCodeValue = (int) $parentAccount->code + 1;
            while (AccHead::where('code', (string) $newCodeValue)->exists()) {
                $newCodeValue++;
            }
            $newCode = (string) $newCodeValue;
        }

        AccHead::create([
            'code' => (string) $newCode,
            'aname' => $accountName,
            'parent_id' => $parentAccount->id,
            'acc_type' => 5,
            'accountable_type' => Employee::class,
            'accountable_id' => $employee->id,
            'is_basic' => 0,
            'deletable' => 0,
            'editable' => 1,
            'is_stock' => 0,
            'is_fund' => 0,
            'start_balance' => 0,
            'credit' => 0,
            'debit' => 0,
            'balance' => 0,
            'crtime' => now(),
            'mdtime' => now(),
        ]);
    }
}
