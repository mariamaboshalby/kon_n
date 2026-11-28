<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('portal_requests')) {
            Schema::create('portal_requests', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->string('type', 30);
                $table->string('requestable_type', 191);
                $table->unsignedBigInteger('requestable_id');
                $table->string('stage', 20)->default('hr');
                $table->string('status', 20)->default('pending');
                $table->foreignId('manager_employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->string('client_request_id', 64)->nullable();
                $table->string('summary', 255)->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->timestamps();

                $table->unique(['employee_id', 'client_request_id'], 'uq_portal_requests_employee_client');
                $table->index(['requestable_type', 'requestable_id'], 'idx_portal_requests_requestable');
                $table->index(['stage', 'status'], 'idx_portal_requests_stage_status');
                $table->index(['manager_employee_id', 'stage', 'status'], 'idx_portal_requests_manager');
                $table->index(['employee_id', 'created_at'], 'idx_portal_requests_employee_created');
            });
        }

        if (! Schema::hasTable('portal_request_actions')) {
            Schema::create('portal_request_actions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('portal_request_id')->constrained('portal_requests')->cascadeOnDelete();
                $table->string('stage', 20);
                $table->string('action', 20);
                $table->string('actor_type', 20);
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('actor_name', 191)->nullable();
                $table->text('note')->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index('portal_request_id', 'idx_portal_request_actions_request');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_request_actions');
        Schema::dropIfExists('portal_requests');
    }
};
