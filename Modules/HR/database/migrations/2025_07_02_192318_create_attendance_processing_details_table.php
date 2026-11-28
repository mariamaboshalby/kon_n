<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_processing_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_processing_id')->constrained('attendance_processings')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('cascade');
            $table->date('attendance_date');
            $table->string('day_type')->nullable(); // holiday, working_day, overtime_day;
            $table->string('attendance_status')->default('absent'); // حضور أو غياب أو إجازة أو إذن;
            $table->time('shift_start_time')->nullable();
            $table->time('shift_end_time')->nullable();
            $table->decimal('working_hours_in_shift', 10, 2)->default(0);
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->decimal('attendance_basic_hours_count', 10, 2)->default(0);
            $table->decimal('attendance_actual_hours_count', 10, 2)->default(0);
            $table->decimal('early_hours', 10, 2)->default(0);
            $table->decimal('attendance_total_hours_count', 10, 2)->default(0);
            $table->decimal('total_due_hourly_salary', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['attendance_processing_id', 'employee_id', 'attendance_date'], 'unique_processing_detail');
            $table->string('project_code')->nullable();
            $table->index(['employee_id', 'attendance_date', 'project_code'], 'idx_attendance_processing_details_employee_date_project_code');
            $table->integer('attendance_overtime_minutes_count')->default(0);
            $table->integer('attendance_late_minutes_count')->default(0);
            $table->index(['attendance_processing_id', 'attendance_date'], 'idx_processing_date');
            $table->index(['employee_id', 'attendance_date'], 'idx_employee_date');
            $table->index(['department_id', 'attendance_date'], 'idx_department_date');
        
        

        
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_processing_details');
    }
};
