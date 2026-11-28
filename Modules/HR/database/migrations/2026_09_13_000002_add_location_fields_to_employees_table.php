<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            if (! Schema::hasColumn('employees', 'require_location')) {
                $table->boolean('require_location')->default(false)->comment('هل الموقع مطلوب لبصمة هذا الموظف');
            }
            if (! Schema::hasColumn('employees', 'location_type')) {
                // company = إحداثيات الشركة من hr_settings, project = أي موقع مرتبط بالمشروع
                $table->enum('location_type', ['company', 'project'])->nullable()->comment('نوع الموقع المطلوب');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $cols = ['require_location', 'location_type'];
            $toDrop = array_filter($cols, fn ($c) => Schema::hasColumn('employees', $c));
            if ($toDrop) {
                $table->dropColumn(array_values($toDrop));
            }
        });
    }
};
