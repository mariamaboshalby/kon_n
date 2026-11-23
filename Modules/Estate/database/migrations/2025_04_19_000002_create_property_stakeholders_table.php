<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * إنشاء جدول أطراف العقار (Many-to-Many)
     */
    public function up(): void
    {
        Schema::create('property_stakeholders', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade')->comment('العقار');
            $table->string('stakeholder_type')->comment('نوع الطرف: client, employee, user');
            $table->unsignedBigInteger('stakeholder_id')->comment('معرف الطرف');
            $table->enum('role', ['owner', 'tenant', 'buyer', 'agent', 'contact'])->comment('دور الطرف');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->boolean('is_primary')->default(false)->comment('هل هو الطرف الرئيسي');
            
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['property_id', 'role']);
            $table->index(['stakeholder_type', 'stakeholder_id']);
            $table->unique(['property_id', 'stakeholder_type', 'stakeholder_id', 'role'], 'unique_property_stakeholder');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_stakeholders');
    }
};
