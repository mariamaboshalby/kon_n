<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. إضافة حالة العهد والحالة عند التسليم
     */
    public function up(): void
    {
        Schema::table('covenants', function (Blueprint $table) {
            if (! Schema::hasColumn('covenants', 'status')) {
                $table->string('status', 30)->nullable()->after('description');
            }

            if (! Schema::hasColumn('covenants', 'handover_status')) {
                $table->string('handover_status')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('covenants', function (Blueprint $table) {
            foreach (['status', 'handover_status'] as $column) {
                if (Schema::hasColumn('covenants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
