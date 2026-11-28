<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'trial_period')) {
                $table->unsignedInteger('trial_period')->nullable()->after('contract_end_date');
            }

            if (! Schema::hasColumn('contracts', 'status')) {
                $table->string('status', 30)->default('active')->after('trial_period');
                $table->index('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'status')) {
                $table->dropIndex(['status']);
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('contracts', 'trial_period')) {
                $table->dropColumn('trial_period');
            }
        });
    }
};
