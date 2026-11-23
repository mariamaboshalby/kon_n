<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('property_types')->nullOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->string('icon')->nullable()->default('las la-building');
            $table->json('attributes')->nullable()->comment('الحقول الخاصة بهذا النوع');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['parent_id', 'is_active']);
        });

        // Replace the old string column with FK
        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex(['property_type']);
            $table->dropColumn('property_type');
            $table->foreignId('property_type_id')->nullable()->after('sku')->constrained('property_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropForeign(['property_type_id']);
            $table->dropColumn('property_type_id');
            $table->string('property_type')->nullable()->after('sku');
            $table->index('property_type');
        });

        Schema::dropIfExists('property_types');
    }
};
