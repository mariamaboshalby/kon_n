<?php

declare(strict_types=1);

namespace Modules\HR\Services\Portal;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\HR\Jobs\SendPortalPushJob;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeeDevice;
use Modules\HR\Models\EmployeePortalNotification;
use Modules\HR\Models\PortalRequest;
use Modules\Notifications\Notifications\GeneralNotification;
use Throwable;

final class PortalNotificationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function notifyEmployee(Employee|int $employee, string $type, string $title, ?string $body = null, array $data = []): void
    {
        $employeeId = $employee instanceof Employee ? (int) $employee->id : $employee;

        EmployeePortalNotification::create([
            'employee_id' => $employeeId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        if (FcmPushService::isConfigured()) {
            SendPortalPushJob::dispatch($employeeId, $title, (string) $body, array_merge($data, ['type' => $type]));
        }
    }

    public function notifyManagerNewRequest(PortalRequest $request): void
    {
        if (! $request->manager_employee_id) {
            return;
        }

        $this->notifyEmployee(
            (int) $request->manager_employee_id,
            'team_request',
            __('hr::portal.notif_team_request_title'),
            __('hr::portal.notif_team_request_body', [
                'name' => $request->employee?->name ?? '',
                'summary' => (string) $request->summary,
            ]),
            ['portal_request_id' => (int) $request->id]
        );
    }

    public function notifyEmployeeDecision(PortalRequest $request): void
    {
        $key = match (true) {
            $request->status === PortalRequest::STATUS_APPROVED => 'notif_request_approved',
            $request->status === PortalRequest::STATUS_REJECTED => 'notif_request_rejected',
            $request->stage === PortalRequest::STAGE_HR => 'notif_request_forwarded',
            default => null,
        };

        if ($key === null) {
            return;
        }

        $this->notifyEmployee(
            (int) $request->employee_id,
            'request_update',
            __('hr::portal.'.$key.'_title'),
            (string) $request->summary,
            ['portal_request_id' => (int) $request->id]
        );
    }

    public function notifyHrNewRequest(PortalRequest $request): void
    {
        $this->notifyHrUsers(
            'edit Portal Requests',
            __('hr::portal.notif_hr_request_title'),
            __('hr::portal.notif_team_request_body', [
                'name' => $request->employee?->name ?? '',
                'summary' => (string) $request->summary,
            ]),
            $this->adminUrl('requests')
        );
    }

    public function notifyHrNewDevice(Employee $employee, EmployeeDevice $device): void
    {
        $this->notifyHrUsers(
            'edit Employee Devices',
            __('hr::portal.notif_hr_device_title'),
            __('hr::portal.notif_hr_device_body', [
                'name' => (string) $employee->name,
                'model' => (string) ($device->model ?? '-'),
            ]),
            $this->adminUrl('devices')
        );
    }

    private function notifyHrUsers(string $permission, string $title, string $message, ?string $url): void
    {
        try {
            $users = User::permission($permission)->limit(50)->get();
            if ($users->isEmpty()) {
                return;
            }

            Notification::send($users, new GeneralNotification($title, $message, $url, 'las la-mobile', 'info'));
        } catch (Throwable $e) {
            Log::warning('HR portal: HR notification failed', ['error' => $e->getMessage()]);
        }
    }

    private function adminUrl(string $tab): ?string
    {
        try {
            return route('hr.employee-portal.index', ['tab' => $tab]);
        } catch (Throwable) {
            return null;
        }
    }
}
