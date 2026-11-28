<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees_jobs', function (Blueprint $table): void {
            if (! Schema::hasColumn('employees_jobs', 'level')) {
                $table->string('level')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees_jobs', function (Blueprint $table): void {
            if (Schema::hasColumn('employees_jobs', 'level')) {
                $table->dropColumn('level');
            }
        });
    }
};
