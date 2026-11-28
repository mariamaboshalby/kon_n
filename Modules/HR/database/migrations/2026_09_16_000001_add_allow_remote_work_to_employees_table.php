<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            if (! Schema::hasColumn('employees', 'allow_remote_work')) {
                $table->boolean('allow_remote_work')
                    ->default(false)
                    ->after('location_type')
                    ->comment('يسمح للموظف بتسجيل البصمة من أي موقع بدون قيود جغرافية');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            if (Schema::hasColumn('employees', 'allow_remote_work')) {
                $table->dropColumn('allow_remote_work');
            }
        });
    }
};
