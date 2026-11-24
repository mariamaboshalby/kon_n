<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('manufacturing_orders') || !Schema::hasColumn('manufacturing_orders', 'branch_id')) {
            return;
        }

        Schema::table('manufacturing_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('manufacturing_orders') || !Schema::hasColumn('manufacturing_orders', 'branch_id')) {
            return;
        }

        Schema::table('manufacturing_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable(false)->change();
        });
    }
};
