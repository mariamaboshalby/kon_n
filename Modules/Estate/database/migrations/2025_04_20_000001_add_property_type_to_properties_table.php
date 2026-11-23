<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // Property type - نوع العقار
            $table->string('property_type')->nullable()->after('sku')->comment('نوع العقار');

            // Extra residential attributes
            $table->decimal('roof_area', 10, 2)->nullable()->after('area')->comment('مساحة الروف - للبنتهاوس');
            $table->decimal('garden_area', 10, 2)->nullable()->after('roof_area')->comment('مساحة الحديقة - للفيلل');

            // Extra commercial attributes
            $table->decimal('facade_width', 8, 2)->nullable()->after('garden_area')->comment('عرض الواجهة - للتجاري');
            $table->boolean('is_corner')->default(false)->after('facade_width')->comment('ركنية - للتجاري');

            // Extra land attributes
            $table->string('land_license_type')->nullable()->after('is_corner')->comment('نوع الترخيص - للأراضي');
            $table->decimal('street_width', 8, 2)->nullable()->after('land_license_type')->comment('عرض الشارع - للأراضي');
            $table->string('land_shape')->nullable()->after('street_width')->comment('شكل الأرض - للأراضي');

            $table->index('property_type');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex(['property_type']);
            $table->dropColumn([
                'property_type', 'roof_area', 'garden_area',
                'facade_width', 'is_corner',
                'land_license_type', 'street_width', 'land_shape',
            ]);
        });
    }
};
