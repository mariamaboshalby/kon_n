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
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('salary')->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 18, 8)->nullable()->default(1.00000000)->after('currency_id');
            $table->decimal('currency_salary', 14, 2)->nullable()->after('exchange_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'exchange_rate', 'currency_salary']);
        });
    }
};
