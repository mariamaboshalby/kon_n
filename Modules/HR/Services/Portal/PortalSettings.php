<?php

declare(strict_types=1);

namespace Modules\HR\Services\Portal;

use Modules\HR\Models\HRSetting;

/**
 * إعدادات بوابة الموظف (tenant-scoped — جدول hr_settings).
 */
final class PortalSettings
{
    public const FLOW_MANAGER_THEN_HR = 'manager_then_hr';

    public const FLOW_HR_ONLY = 'hr_only';

    public const FLOW_MANAGER_ONLY = 'manager_only';

    public const FLOWS = [
        self::FLOW_MANAGER_THEN_HR,
        self::FLOW_HR_ONLY,
        self::FLOW_MANAGER_ONLY,
    ];

    public function __construct(private readonly ?HRSetting $setting)
    {
    }

    public static function current(): self
    {
        return new self(HRSetting::getCompanyDefault());
    }

    public function model(): ?HRSetting
    {
        return $this->setting;
    }

    public function enabled(): bool
    {
        return $this->bool('portal_enabled', false);
    }

    public function requireBiometric(): bool
    {
        return $this->bool('portal_require_biometric', true);
    }

    public function deviceBinding(): bool
    {
        return $this->bool('portal_device_binding', true);
    }

    public function maxDevices(): int
    {
        return max(1, $this->int('portal_max_devices_per_employee', 1));
    }

    public function enforceGeofence(): bool
    {
        return $this->bool('portal_enforce_geofence', true);
    }

    public function maxGpsAccuracy(): int
    {
        return max(5, $this->int('portal_max_gps_accuracy_meters', 50));
    }

    public function blockMockLocation(): bool
    {
        return $this->bool('portal_block_mock_location', true);
    }

    public function selfieRequired(): bool
    {
        return $this->bool('portal_selfie_required', false);
    }

    public function approvalFlow(): string
    {
        $flow = (string) ($this->setting?->portal_approval_flow ?? self::FLOW_MANAGER_THEN_HR);

        return in_array($flow, self::FLOWS, true) ? $flow : self::FLOW_MANAGER_THEN_HR;
    }

    public function showPayslip(): bool
    {
        return $this->bool('portal_show_payslip', false);
    }

    public function checkinReminderMinutes(): int
    {
        return max(0, $this->int('portal_checkin_reminder_minutes', 0));
    }

    /**
     * @return array<string, mixed>
     */
    public function toClientArray(): array
    {
        return [
            'require_biometric' => $this->requireBiometric(),
            'device_binding' => $this->deviceBinding(),
            'enforce_geofence' => $this->enforceGeofence(),
            'max_gps_accuracy' => $this->maxGpsAccuracy(),
            'block_mock_location' => $this->blockMockLocation(),
            'selfie_required' => $this->selfieRequired(),
            'show_payslip' => $this->showPayslip(),
            'push_enabled' => FcmPushService::isConfigured(),
        ];
    }

    private function bool(string $key, bool $default): bool
    {
        $value = $this->setting?->getAttribute($key);

        return $value === null ? $default : (bool) $value;
    }

    private function int(string $key, int $default): int
    {
        $value = $this->setting?->getAttribute($key);

        return $value === null ? $default : (int) $value;
    }
}
