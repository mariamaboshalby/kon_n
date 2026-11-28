<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('national_id_number')->nullable()->after('nationalId');
            $table->date('national_id_expiry')->nullable()->after('national_id_number');
            $table->string('residence_number')->nullable()->after('national_id_expiry');
            $table->date('residence_expiry')->nullable()->after('residence_number');
            $table->string('license_number')->nullable()->after('residence_expiry');
            $table->date('license_expiry')->nullable()->after('license_number');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'national_id_number', 'national_id_expiry',
                'residence_number', 'residence_expiry',
                'license_number', 'license_expiry',
            ]);
        });
    }
};
