<?php

declare(strict_types=1);

namespace Modules\HR\Services\Portal;

use Modules\HR\Models\Employee;
use Modules\HR\Models\EmployeeDevice;

final class PortalEmployeePresenter
{
    /**
     * @return array<string, mixed>
     */
    public function profile(Employee $employee, ?EmployeeDevice $device = null): array
    {
        $employee->loadMissing(['department:id,title', 'job:id,title', 'shift', 'lineManager:id,name']);
        $settings = PortalSettings::current();
        $shift = $employee->shift;

        return [
            'id' => (int) $employee->id,
            'name' => (string) $employee->name,
            'finger_print_id' => $employee->finger_print_id,
            'phone' => $employee->phone,
            'email' => $employee->email,
            'image_url' => $employee->image_url,
            'department' => $employee->department?->title,
            'job' => $employee->job?->title,
            'manager' => $employee->lineManager?->name,
            'date_of_hire' => $employee->date_of_hire?->format('Y-m-d'),
            'is_manager' => $employee->subordinates()->exists(),
            'require_location' => (bool) $employee->require_location,
            'location_type' => $employee->location_type,
            'allow_remote_work' => (bool) $employee->allow_remote_work,
            'must_change_password' => $employee->portal_password_changed_at === null,
            'account_status' => PortalAuthService::isEmployeeInactive($employee) ? 'inactive' : 'active',
            'account_status_message' => PortalAuthService::isEmployeeInactive($employee) ? __('hr::portal.account_disabled') : null,
            'shift' => $shift ? [
                'name' => $shift->name,
                'start_time' => substr((string) $shift->start_time, 0, 5),
                'end_time' => substr((string) $shift->end_time, 0, 5),
                'days' => json_decode((string) $shift->days, true) ?: [],
            ] : null,
            'device' => $device ? [
                'id' => (int) $device->id,
                'status' => $device->status,
            ] : null,
            'settings' => $settings->toClientArray(),
        ];
    }
}
