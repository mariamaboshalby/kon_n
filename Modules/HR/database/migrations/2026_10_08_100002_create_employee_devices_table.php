<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_devices')) {
            return;
        }

        Schema::create('employee_devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('device_uuid', 100);
            $table->string('platform', 20)->nullable();
            $table->string('model', 120)->nullable();
            $table->string('os_version', 40)->nullable();
            $table->string('app_version', 20)->nullable();
            $table->string('fcm_token', 255)->nullable();
            $table->enum('status', ['pending', 'approved', 'revoked'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'device_uuid'], 'uq_employee_devices_employee_uuid');
            $table->index(['status', 'created_at'], 'idx_employee_devices_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_devices');
    }
};
