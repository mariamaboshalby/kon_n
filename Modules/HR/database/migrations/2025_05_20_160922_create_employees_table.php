<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesTable extends Migration
{

    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('phone')->unique();
            $table->string('email')->unique();
            $table->string('image')->nullable();
            $table->string('position')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->enum('gender', array('male', 'female'))->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nationalId')->nullable()->unique();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->enum('education', ['high_school', 'diploma', 'bachelor', 'master', 'doctorate'])->nullable();
            $table->text('information')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->nullable();
            $table->foreignId('country_id')->nullable()->constrained('countries')->onDelete('restrict');
            $table->foreignId('city_id')->nullable()->constrained('cities')->onDelete('restrict');
            $table->foreignId('state_id')->nullable()->constrained('states')->onDelete('restrict');
            $table->foreignId('town_id')->nullable()->constrained('towns')->onDelete('restrict');
            $table->foreignId('job_id')->nullable()->constrained('employees_jobs')->onDelete('restrict');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('restrict');
            $table->date('date_of_hire')->nullable();
            $table->date('date_of_fire')->nullable();
            $table->enum('job_level', ['junior', 'mid', 'senior', 'lead', 'manager'])->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->integer('finger_print_id')->nullable()->unique();
            $table->string('finger_print_name')->nullable()->unique();
            $table->enum('salary_type', ['monthly', 'daily', 'hourly'])->nullable();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->onDelete('restrict');
            $table->string('password')->nullable();
            $table->decimal('additional_hour_calculation', 10, 2)->nullable();
            $table->decimal('additional_day_calculation', 10, 2)->nullable();
            $table->timestamps();
            $table->decimal('late_hour_calculation', 10, 2)->nullable();
            $table->decimal('late_day_calculation', 10, 2)->nullable();
            $table->date('date_of_joining')->nullable();
            $table->integer('days_off')->default(0);
            $table->string('device_id')->nullable()->unique();
            $table->string('device_info')->nullable();
            $table->softDeletes();
        
        
        
        
        
        
        
        });
    }

    public function down()
    {
        Schema::drop('employees');
    }
}
