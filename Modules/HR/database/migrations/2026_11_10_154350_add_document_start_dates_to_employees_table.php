<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('national_id_start')->nullable()->after('national_id_number');
            $table->date('residence_start')->nullable()->after('residence_number');
            $table->date('license_start')->nullable()->after('license_number');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['national_id_start', 'residence_start', 'license_start']);
        });
    }
};
