<?php

declare(strict_types=1);

namespace Modules\HR\Console;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Modules\HR\Models\Employee;
use Modules\Notifications\Notifications\GeneralNotification;
use Throwable;

class CheckEmployeeDocumentExpiryCommand extends Command
{
    protected $signature = 'hr:check-document-expiry {--days= : Override warning days}';
    protected $description = 'Send notifications before employee documents (ID/Residence/License) expire';

    private const DEFAULT_DAYS = 30;

    private const DOCUMENTS = [
        'national_id_expiry' => ['label' => 'الهوية الوطنية', 'icon' => 'las la-id-card'],
        'residence_expiry'   => ['label' => 'الإقامة',        'icon' => 'las la-home'],
        'license_expiry'     => ['label' => 'الرخصة',         'icon' => 'las la-car'],
    ];

    public function handle(): int
    {
        tenancy()->runForMultiple(null, function ($tenant): void {
            try {
                $sent = $this->checkForCurrentTenant();
                if ($sent > 0) {
                    $this->line("tenant {$tenant->getTenantKey()}: {$sent} document expiry notification(s)");
                }
            } catch (Throwable $e) {
                $this->warn("tenant {$tenant->getTenantKey()}: " . $e->getMessage());
            }
        });

        return self::SUCCESS;
    }

    private function checkForCurrentTenant(): int
    {
        if (! Schema::hasColumn('employees', 'national_id_expiry')) {
            return 0;
        }

        $days = (int) ($this->option('days') ?? self::DEFAULT_DAYS);
        $today = Carbon::now(config('app.timezone'))->startOfDay();
        $soon  = $today->copy()->addDays($days);

        $recipients = User::permission('view Employees')->limit(100)->get();
        if ($recipients->isEmpty()) {
            return 0;
        }

        $sent = 0;

        foreach (self::DOCUMENTS as $field => $meta) {
            $expiring = Employee::withoutGlobalScopes()
                ->whereNotNull($field)
                ->whereDate($field, '>=', $today->toDateString())
                ->whereDate($field, '<=', $soon->toDateString())
                ->get(['id', 'name', $field]);

            foreach ($expiring as $employee) {
                $expiry = Carbon::parse($employee->$field)->startOfDay();
                $marker = "doc-expiry:{$employee->id}:{$field}:{$expiry->toDateString()}";

                if ($this->alreadyNotified($marker)) {
                    continue;
                }

                $remaining = max(0, $today->diffInDays($expiry, false));

                Notification::send($recipients, new GeneralNotification(
                    title: __('hr::hr.document_expiry_alert_title', ['doc' => $meta['label']]),
                    message: __('hr::hr.document_expiry_alert_body', [
                        'employee' => $employee->name,
                        'doc'      => $meta['label'],
                        'end_date' => $expiry->toDateString(),
                        'days'     => $remaining,
                    ]),
                    url: route('employees.edit', $employee->id),
                    icon: $meta['icon'],
                    type: 'warning',
                    extraData: ['dedupe_key' => $marker],
                ));
                $sent++;
            }
        }

        return $sent;
    }

    private function alreadyNotified(string $marker): bool
    {
        try {
            return DB::table('notifications')
                ->where('data', 'like', '%' . $marker . '%')
                ->exists();
        } catch (Throwable) {
            return false;
        }
    }
}
