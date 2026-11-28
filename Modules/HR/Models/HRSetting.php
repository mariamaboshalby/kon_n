<?php

declare(strict_types=1);

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HRSetting extends Model
{
    use HasFactory;

    protected $table = 'hr_settings';

    protected $fillable = [
        'company_max_leave_percentage',
        'company_latitude',
        'company_longitude',
        'company_location_radius',
        'portal_enabled',
        'portal_require_biometric',
        'portal_device_binding',
        'portal_max_devices_per_employee',
        'portal_enforce_geofence',
        'portal_max_gps_accuracy_meters',
        'portal_block_mock_location',
        'portal_selfie_required',
        'portal_approval_flow',
        'portal_show_payslip',
        'portal_checkin_reminder_minutes',
        'shift_day_start_hour',
        'shift_day_end_hour',
        'shift_week_start_day',
    ];

    protected $casts = [
        'company_max_leave_percentage' => 'decimal:2',
        'company_latitude'             => 'decimal:7',
        'company_longitude'            => 'decimal:7',
        'company_location_radius'      => 'integer',
        'portal_enabled'                  => 'boolean',
        'portal_require_biometric'        => 'boolean',
        'portal_device_binding'           => 'boolean',
        'portal_max_devices_per_employee' => 'integer',
        'portal_enforce_geofence'         => 'boolean',
        'portal_max_gps_accuracy_meters'  => 'integer',
        'portal_block_mock_location'      => 'boolean',
        'portal_selfie_required'          => 'boolean',
        'portal_show_payslip'             => 'boolean',
        'portal_checkin_reminder_minutes' => 'integer',
        'shift_day_start_hour'            => 'integer',
        'shift_day_end_hour'              => 'integer',
    ];

    /**
     * الحصول على الإعدادات الافتراضية للشركة
     */
    public static function getCompanyDefault(): ?self
    {
        return self::first();
    }

    /**
     * الحصول على النسبة المئوية القصوى للشركة
     */
    public static function getCompanyMaxLeavePercentage(): ?float
    {
        $setting = self::getCompanyDefault();

        return $setting ? (float) $setting->company_max_leave_percentage : null;
    }

    /**
     * هل إحداثيات الشركة محددة؟
     * يقرأ من جدول settings
     */
    public function hasLocation(): bool
    {
        $s = \App\Models\Setting::first();
        return $s && $s->company_latitude !== null && $s->company_longitude !== null;
    }

    /**
     * إرجاع إحداثيات الشركة من جدول settings أو null
     */
    public static function getCompanyLocation(): ?array
    {
        $s = \App\Models\Setting::first();

        if (! $s || $s->company_latitude === null || $s->company_longitude === null) {
            return null;
        }

        return [
            'latitude'  => (float) $s->company_latitude,
            'longitude' => (float) $s->company_longitude,
            'radius'    => (int) ($s->company_location_radius ?? 200),
        ];
    }
}
