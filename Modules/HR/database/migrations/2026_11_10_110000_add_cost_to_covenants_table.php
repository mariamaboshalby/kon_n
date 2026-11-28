<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إضافة حقل تكلفة العهدة
     */
    public function up(): void
    {
        Schema::table('covenants', function (Blueprint $table) {
            if (! Schema::hasColumn('covenants', 'cost')) {
                $table->decimal('cost', 12, 2)->nullable()->after('handover_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('covenants', function (Blueprint $table) {
            if (Schema::hasColumn('covenants', 'cost')) {
                $table->dropColumn('cost');
            }
        });
    }
};
