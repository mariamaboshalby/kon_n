<?php

declare(strict_types=1);

namespace Modules\HR\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Modules\HR\Models\Attendance;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeePortalNotification;
use Modules\HR\Models\LeaveRequest;
use Modules\HR\Services\Portal\PortalNotificationService;
use Modules\HR\Services\Portal\PortalSettings;
use Throwable;

/**
 * تذكير الموظفين الذين لم يسجّلوا حضوراً بعد بداية الوردية بـ X دقيقة (لكل tenant).
 */
class PortalCheckinRemindersCommand extends Command
{
    protected $signature = 'hr:portal-checkin-reminders';

    protected $description = 'Send employee portal check-in reminders for all tenants';

    private const WINDOW_MINUTES = 5;

    private const DAY_MAP = [
        0 => 'sunday',
        1 => 'monday',
        2 => 'tuesday',
        3 => 'wednesday',
        4 => 'thursday',
        5 => 'friday',
        6 => 'saturday',
    ];

    public function handle(): int
    {
        tenancy()->runForMultiple(null, function ($tenant): void {
            try {
                $sent = $this->remindForCurrentTenant();
                if ($sent > 0) {
                    $this->line("tenant {$tenant->getTenantKey()}: {$sent} reminder(s)");
                }
            } catch (Throwable $e) {
                $this->warn("tenant {$tenant->getTenantKey()}: ".$e->getMessage());
            }
        });

        return self::SUCCESS;
    }

    private function remindForCurrentTenant(): int
    {
        if (! Schema::hasTable('employee_portal_notifications') || ! Schema::hasColumn('hr_settings', 'portal_enabled')) {
            return 0;
        }

        $settings = PortalSettings::current();
        $minutes = $settings->checkinReminderMinutes();
        if (! $settings->enabled() || $minutes <= 0) {
            return 0;
        }

        $now = Carbon::now(config('app.timezone'));
        $today = $now->toDateString();
        $dayName = self::DAY_MAP[$now->dayOfWeek];
        $notifications = app(PortalNotificationService::class);
        $sent = 0;

        Employee::query()
            ->with('shift')
            ->where('portal_enabled', true)
            ->whereNull('date_of_fire')
            ->whereNotNull('shift_id')
            ->chunkById(500, function ($employees) use ($now, $today, $dayName, $minutes, $notifications, &$sent): void {
                foreach ($employees as $employee) {
                    $shift = $employee->shift;
                    if (! $shift || ! $shift->start_time) {
                        continue;
                    }

                    $days = json_decode((string) $shift->days, true);
                    if (is_array($days) && $days !== [] && ! in_array($dayName, $days, true)) {
                        continue;
                    }

                    $remindAt = Carbon::parse($today.' '.substr((string) $shift->start_time, 0, 8), config('app.timezone'))
                        ->addMinutes($minutes);
                    if ($remindAt->gt($now) || $remindAt->lte($now->copy()->subMinutes(self::WINDOW_MINUTES))) {
                        continue;
                    }

                    $checkedIn = Attendance::query()
                        ->where('employee_id', $employee->id)
                        ->whereDate('date', $today)
                        ->where('type', 'check_in')
                        ->exists();
                    if ($checkedIn) {
                        continue;
                    }

                    $onLeave = LeaveRequest::query()
                        ->where('employee_id', $employee->id)
                        ->where('status', 'approved')
                        ->whereDate('start_date', '<=', $today)
                        ->whereDate('end_date', '>=', $today)
                        ->exists();
                    if ($onLeave) {
                        continue;
                    }

                    $alreadySent = EmployeePortalNotification::query()
                        ->where('employee_id', $employee->id)
                        ->where('type', 'checkin_reminder')
                        ->whereDate('created_at', $today)
                        ->exists();
                    if ($alreadySent) {
                        continue;
                    }

                    $notifications->notifyEmployee(
                        $employee,
                        'checkin_reminder',
                        __('hr::portal.notif_checkin_reminder_title'),
                        __('hr::portal.notif_checkin_reminder_body', ['time' => substr((string) $shift->start_time, 0, 5)])
                    );
                    $sent++;
                }
            });

        return $sent;
    }
}
