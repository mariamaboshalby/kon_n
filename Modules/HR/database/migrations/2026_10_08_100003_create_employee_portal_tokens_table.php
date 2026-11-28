<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_portal_tokens')) {
            return;
        }

        Schema::create('employee_portal_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('employee_device_id')->nullable()->constrained('employee_devices')->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->string('name', 100)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('employee_id', 'idx_portal_tokens_employee');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_portal_tokens');
    }
};
