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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->unsignedBigInteger('employee_attendance_finger_print_id')->nullable();
            $table->string('employee_attendance_finger_print_name')->nullable();
            $table->enum('type', ['check_in', 'check_out']);
            $table->date('date')->default(now()->format('Y-m-d'));
            $table->time('time')->default(now()->format('H:i:s'));
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('attandance_processing_id')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index('user_id');
            $table->string('project_code')->nullable();
            $table->index(['employee_id', 'date'], 'idx_attendances_employee_date');
            $table->index('date', 'idx_attendances_date');
            $table->string('device_type')->nullable();
            $table->json('location')->nullable();
        

        
        
        
        
        
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
