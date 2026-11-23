<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('settings', 'pos_enable_print')) {
                $table->boolean('pos_enable_print')->default(false)->after('restaurant_inventory_account');
            }
            if (! Schema::hasColumn('settings', 'pos_silent_print')) {
                $table->boolean('pos_silent_print')->default(false)->after('pos_enable_print');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropColumn(['pos_enable_print', 'pos_silent_print']);
        });
    }
};
