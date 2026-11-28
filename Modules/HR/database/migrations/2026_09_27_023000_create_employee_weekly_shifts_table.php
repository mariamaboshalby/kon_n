<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_weekly_shifts')) {
            return;
        }

        Schema::create('employee_weekly_shifts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->string('weekday', 16);
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'weekday'], 'employee_weekly_shifts_employee_day_unique');
            $table->index(['weekday', 'shift_id'], 'employee_weekly_shifts_day_shift_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_weekly_shifts');
    }
};
