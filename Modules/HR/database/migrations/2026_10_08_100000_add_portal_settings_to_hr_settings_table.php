<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @return array<string, callable(Blueprint): mixed> */
    private function columns(): array
    {
        return [
            'portal_enabled' => fn (Blueprint $t) => $t->boolean('portal_enabled')->default(false),
            'portal_require_biometric' => fn (Blueprint $t) => $t->boolean('portal_require_biometric')->default(true),
            'portal_device_binding' => fn (Blueprint $t) => $t->boolean('portal_device_binding')->default(true),
            'portal_max_devices_per_employee' => fn (Blueprint $t) => $t->unsignedTinyInteger('portal_max_devices_per_employee')->default(1),
            'portal_enforce_geofence' => fn (Blueprint $t) => $t->boolean('portal_enforce_geofence')->default(true),
            'portal_max_gps_accuracy_meters' => fn (Blueprint $t) => $t->unsignedSmallInteger('portal_max_gps_accuracy_meters')->default(50),
            'portal_block_mock_location' => fn (Blueprint $t) => $t->boolean('portal_block_mock_location')->default(true),
            'portal_selfie_required' => fn (Blueprint $t) => $t->boolean('portal_selfie_required')->default(false),
            'portal_approval_flow' => fn (Blueprint $t) => $t->string('portal_approval_flow', 30)->default('manager_then_hr'),
            'portal_show_payslip' => fn (Blueprint $t) => $t->boolean('portal_show_payslip')->default(false),
            'portal_checkin_reminder_minutes' => fn (Blueprint $t) => $t->unsignedSmallInteger('portal_checkin_reminder_minutes')->default(0),
        ];
    }

    public function up(): void
    {
        if (! Schema::hasTable('hr_settings')) {
            return;
        }

        Schema::table('hr_settings', function (Blueprint $table): void {
            foreach ($this->columns() as $name => $definition) {
                if (! Schema::hasColumn('hr_settings', $name)) {
                    $definition($table);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('hr_settings')) {
            return;
        }

        Schema::table('hr_settings', function (Blueprint $table): void {
            $existing = array_values(array_filter(
                array_keys($this->columns()),
                fn (string $c) => Schema::hasColumn('hr_settings', $c)
            ));

            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};
