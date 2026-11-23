<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // pos_enable_print & pos_silent_print قد تكون موجودة بالفعل
            if (! Schema::hasColumn('settings', 'pos_enable_print')) {
                $table->boolean('pos_enable_print')->default(false);
            }
            if (! Schema::hasColumn('settings', 'pos_silent_print')) {
                $table->boolean('pos_silent_print')->default(false);
            }

            // ── إعدادات فاتورة الكاشير ──────────────────────────────────
            if (! Schema::hasColumn('settings', 'cashier_receipt_title')) {
                $table->string('cashier_receipt_title', 100)->nullable();
            }
            if (! Schema::hasColumn('settings', 'cashier_receipt_subtitle')) {
                $table->string('cashier_receipt_subtitle', 200)->nullable();
            }
            if (! Schema::hasColumn('settings', 'cashier_receipt_footer')) {
                $table->string('cashier_receipt_footer', 300)->nullable();
            }
            if (! Schema::hasColumn('settings', 'cashier_paper_width')) {
                $table->string('cashier_paper_width', 10)->default('78mm');
            }
            if (! Schema::hasColumn('settings', 'cashier_font_size')) {
                $table->unsignedTinyInteger('cashier_font_size')->default(11);
            }
            if (! Schema::hasColumn('settings', 'cashier_show_logo')) {
                $table->boolean('cashier_show_logo')->default(true);
            }
            if (! Schema::hasColumn('settings', 'cashier_show_cashier_name')) {
                $table->boolean('cashier_show_cashier_name')->default(true);
            }
            if (! Schema::hasColumn('settings', 'cashier_show_table')) {
                $table->boolean('cashier_show_table')->default(true);
            }
            if (! Schema::hasColumn('settings', 'cashier_show_barcode')) {
                $table->boolean('cashier_show_barcode')->default(true);
            }
            if (! Schema::hasColumn('settings', 'cashier_show_notes')) {
                $table->boolean('cashier_show_notes')->default(true);
            }
            if (! Schema::hasColumn('settings', 'cashier_show_payment_details')) {
                $table->boolean('cashier_show_payment_details')->default(true);
            }
            if (! Schema::hasColumn('settings', 'cashier_separator_style')) {
                $table->string('cashier_separator_style', 20)->default('dashed');
            }

            // ── إعدادات ورقة التحضير (المطبخ) ──────────────────────────
            if (! Schema::hasColumn('settings', 'kitchen_receipt_title')) {
                $table->string('kitchen_receipt_title', 100)->nullable();
            }
            if (! Schema::hasColumn('settings', 'kitchen_receipt_footer')) {
                $table->string('kitchen_receipt_footer', 300)->nullable();
            }
            if (! Schema::hasColumn('settings', 'kitchen_paper_width')) {
                $table->string('kitchen_paper_width', 10)->default('78mm');
            }
            if (! Schema::hasColumn('settings', 'kitchen_font_size')) {
                $table->unsignedTinyInteger('kitchen_font_size')->default(14);
            }
            if (! Schema::hasColumn('settings', 'kitchen_show_table')) {
                $table->boolean('kitchen_show_table')->default(true);
            }
            if (! Schema::hasColumn('settings', 'kitchen_show_cashier_name')) {
                $table->boolean('kitchen_show_cashier_name')->default(true);
            }
            if (! Schema::hasColumn('settings', 'kitchen_show_notes')) {
                $table->boolean('kitchen_show_notes')->default(true);
            }
            if (! Schema::hasColumn('settings', 'kitchen_show_order_time')) {
                $table->boolean('kitchen_show_order_time')->default(true);
            }
            if (! Schema::hasColumn('settings', 'kitchen_show_prices')) {
                $table->boolean('kitchen_show_prices')->default(false);
            }
            if (! Schema::hasColumn('settings', 'kitchen_separator_style')) {
                $table->string('kitchen_separator_style', 20)->default('solid');
            }
            if (! Schema::hasColumn('settings', 'kitchen_copies')) {
                $table->unsignedTinyInteger('kitchen_copies')->default(1);
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $cols = [
                'cashier_receipt_title', 'cashier_receipt_subtitle', 'cashier_receipt_footer',
                'cashier_paper_width', 'cashier_font_size',
                'cashier_show_logo', 'cashier_show_cashier_name', 'cashier_show_table',
                'cashier_show_barcode', 'cashier_show_notes', 'cashier_show_payment_details',
                'cashier_separator_style',
                'kitchen_receipt_title', 'kitchen_receipt_footer',
                'kitchen_paper_width', 'kitchen_font_size',
                'kitchen_show_table', 'kitchen_show_cashier_name', 'kitchen_show_notes',
                'kitchen_show_order_time', 'kitchen_show_prices',
                'kitchen_separator_style', 'kitchen_copies',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
