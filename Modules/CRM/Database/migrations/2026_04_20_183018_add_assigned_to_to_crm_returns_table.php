<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_returns', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete()->after('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('crm_returns', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\User::class, 'assigned_to');
            $table->dropColumn('assigned_to');
        });
    }
};
