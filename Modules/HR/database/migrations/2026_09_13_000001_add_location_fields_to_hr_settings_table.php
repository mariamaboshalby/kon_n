<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('hr_settings', 'company_latitude')) {
                $table->decimal('company_latitude', 10, 7)->nullable()->comment('خط العرض لموقع الشركة');
            }
            if (! Schema::hasColumn('hr_settings', 'company_longitude')) {
                $table->decimal('company_longitude', 10, 7)->nullable()->comment('خط الطول لموقع الشركة');
            }
            if (! Schema::hasColumn('hr_settings', 'company_location_radius')) {
                $table->unsignedSmallInteger('company_location_radius')->default(200)->comment('نطاق الشركة بالمتر');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hr_settings', function (Blueprint $table): void {
            $cols = ['company_latitude', 'company_longitude', 'company_location_radius'];
            $toDrop = array_filter($cols, fn ($c) => Schema::hasColumn('hr_settings', $c));
            if ($toDrop) {
                $table->dropColumn(array_values($toDrop));
            }
        });
    }
};
