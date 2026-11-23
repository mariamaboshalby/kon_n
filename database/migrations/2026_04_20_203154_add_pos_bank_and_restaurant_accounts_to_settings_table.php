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
            // POS general settings
            if (! Schema::hasColumn('settings', 'def_pos_bank')) {
                $table->integer('def_pos_bank')->nullable()->after('def_pos_fund');
            }
            if (! Schema::hasColumn('settings', 'def_pos_price_group')) {
                $table->integer('def_pos_price_group')->nullable()->after('def_pos_bank');
            }

            // Restaurant accounting accounts
            if (! Schema::hasColumn('settings', 'restaurant_kitchen_store')) {
                $table->integer('restaurant_kitchen_store')->nullable()->after('def_pos_price_group');
            }
            if (! Schema::hasColumn('settings', 'restaurant_operating_account')) {
                $table->integer('restaurant_operating_account')->nullable()->after('restaurant_kitchen_store');
            }
            if (! Schema::hasColumn('settings', 'restaurant_sales_account')) {
                $table->integer('restaurant_sales_account')->nullable()->after('restaurant_operating_account');
            }
            if (! Schema::hasColumn('settings', 'restaurant_cogs_account')) {
                $table->integer('restaurant_cogs_account')->nullable()->after('restaurant_sales_account');
            }
            if (! Schema::hasColumn('settings', 'restaurant_inventory_account')) {
                $table->integer('restaurant_inventory_account')->nullable()->after('restaurant_cogs_account');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropColumn([
                'def_pos_bank',
                'def_pos_price_group',
                'restaurant_kitchen_store',
                'restaurant_operating_account',
                'restaurant_sales_account',
                'restaurant_cogs_account',
                'restaurant_inventory_account',
            ]);
        });
    }
};
