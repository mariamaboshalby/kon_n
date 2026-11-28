<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_portal_notifications')) {
            return;
        }

        Schema::create('employee_portal_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('title', 191);
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'read_at'], 'idx_portal_notifications_employee_read');
            $table->index(['employee_id', 'created_at'], 'idx_portal_notifications_employee_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_portal_notifications');
    }
};
