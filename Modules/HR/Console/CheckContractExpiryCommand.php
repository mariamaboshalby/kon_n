<?php

declare(strict_types=1);

namespace Modules\HR\Console;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Modules\HR\Models\Contract;
use Modules\Notifications\Notifications\GeneralNotification;
use Throwable;

/**
 * تنبيه قبل انتهاء العقد بفترة قابلة للضبط (افتراضياً 30 يوم) — لكل tenant.
 * يرسل إشعار (جرس) للمستخدمين الذين يملكون صلاحية "view Contracts" (HR/المدير).
 */
class CheckContractExpiryCommand extends Command
{
    protected $signature = 'hr:check-contract-expiry {--days= : Override warning days (for testing)}';

    protected $description = 'Send notifications before employee contracts expire for all tenants';

    private const DEFAULT_DAYS = 30;

    public function handle(): int
    {
        tenancy()->runForMultiple(null, function ($tenant): void {
            try {
                $sent = $this->checkForCurrentTenant();
                if ($sent > 0) {
                    $this->line("tenant {$tenant->getTenantKey()}: {$sent} contract expiry notification(s)");
                }
            } catch (Throwable $e) {
                $this->warn("tenant {$tenant->getTenantKey()}: ".$e->getMessage());
            }
        });

        return self::SUCCESS;
    }

    private function checkForCurrentTenant(): int
    {
        if (! Schema::hasTable('contracts')) {
            return 0;
        }

        $days = (int) ($this->option('days') ?? setting('contract_expiry_warning_days', self::DEFAULT_DAYS));
        if ($days <= 0) {
            return 0;
        }

        $today = Carbon::now(config('app.timezone'))->startOfDay();
        $soon = $today->copy()->addDays($days);

        $expiring = Contract::withoutGlobalScopes()
            ->whereNotNull('contract_end_date')
            ->whereDate('contract_end_date', '>=', $today->toDateString())
            ->whereDate('contract_end_date', '<=', $soon->toDateString())
            ->with('employee')
            ->get();

        if ($expiring->isEmpty()) {
            return 0;
        }

        $recipients = User::permission('view Contracts')->limit(100)->get();
        if ($recipients->isEmpty()) {
            return 0;
        }

        $sent = 0;
        foreach ($expiring as $contract) {
            $endDate = Carbon::parse($contract->contract_end_date)->startOfDay();
            $marker = "contract-expiry:{$contract->id}:{$endDate->toDateString()}";

            // لا تُرسل أكثر من مرة لنفس العقد/تاريخ الانتهاء (تجنّب التكرار اليومي).
            if ($this->alreadyNotified($marker)) {
                continue;
            }

            $remainingDays = max(0, $today->diffInDays($endDate, false));
            $employeeName = (string) ($contract->employee?->name ?? ('#'.$contract->id));

            Notification::send($recipients, new GeneralNotification(
                title: __('hr::hr.contract_expiry_alert_title'),
                message: __('hr::hr.contract_expiry_alert_body', [
                    'employee' => $employeeName,
                    'end_date' => $endDate->toDateString(),
                    'days' => $remainingDays,
                ]),
                url: $this->contractsUrl(),
                icon: 'las la-file-signature',
                type: 'warning',
                extraData: ['dedupe_key' => $marker],
            ));
            $sent++;
        }

        return $sent;
    }

    private function alreadyNotified(string $marker): bool
    {
        try {
            return DB::table('notifications')
                ->where('data', 'like', '%'.$marker.'%')
                ->exists();
        } catch (Throwable) {
            return false;
        }
    }

    private function contractsUrl(): ?string
    {
        try {
            return route('recruitment.contracts.index');
        } catch (Throwable) {
            return null;
        }
    }
}
