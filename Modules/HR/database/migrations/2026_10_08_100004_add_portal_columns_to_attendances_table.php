<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ALTER على جدول attendances (قد يكون كبيراً) — يُفضَّل تشغيله خارج ساعات الذروة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            if (! Schema::hasColumn('attendances', 'employee_device_id')) {
                $table->unsignedBigInteger('employee_device_id')->nullable();
                $table->index('employee_device_id', 'idx_attendances_device');
            }
            if (! Schema::hasColumn('attendances', 'distance_meters')) {
                $table->unsignedInteger('distance_meters')->nullable();
            }
            if (! Schema::hasColumn('attendances', 'gps_accuracy')) {
                $table->decimal('gps_accuracy', 8, 2)->nullable();
            }
            if (! Schema::hasColumn('attendances', 'is_remote')) {
                $table->boolean('is_remote')->default(false);
            }
            if (! Schema::hasColumn('attendances', 'is_suspicious')) {
                $table->boolean('is_suspicious')->default(false);
            }
            if (! Schema::hasColumn('attendances', 'suspicious_reason')) {
                $table->string('suspicious_reason', 255)->nullable();
            }
            if (! Schema::hasColumn('attendances', 'client_request_id')) {
                $table->string('client_request_id', 64)->nullable();
                $table->unique(['employee_id', 'client_request_id'], 'uq_attendances_employee_client_req');
            }
            if (! Schema::hasColumn('attendances', 'selfie_path')) {
                $table->string('selfie_path', 255)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            if (Schema::hasColumn('attendances', 'client_request_id')) {
                $table->dropUnique('uq_attendances_employee_client_req');
            }
            if (Schema::hasColumn('attendances', 'employee_device_id')) {
                $table->dropIndex('idx_attendances_device');
            }

            $cols = array_values(array_filter([
                'employee_device_id',
                'distance_meters',
                'gps_accuracy',
                'is_remote',
                'is_suspicious',
                'suspicious_reason',
                'client_request_id',
                'selfie_path',
            ], fn (string $c) => Schema::hasColumn('attendances', $c)));

            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
