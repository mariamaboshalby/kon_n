<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            if (! Schema::hasColumn('employees', 'name_en')) {
                $table->string('name_en')->nullable()->after('name');
            }

            if (! Schema::hasColumn('employees', 'code')) {
                $table->string('code')->nullable()->after('name_en')->unique();
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            if (Schema::hasColumn('employees', 'code')) {
                $table->dropUnique(['code']);
                $table->dropColumn('code');
            }

            if (Schema::hasColumn('employees', 'name_en')) {
                $table->dropColumn('name_en');
            }
        });
    }
};
