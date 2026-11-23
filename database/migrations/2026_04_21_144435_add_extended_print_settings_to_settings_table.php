<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** الحقول الجديدة فقط — الحقول القديمة موجودة بالفعل */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $new = [
                // ── فاتورة الكاشير: عناصر إضافية ──────────────────────
                'cashier_show_order_number'   => fn () => $table->boolean('cashier_show_order_number')->default(true),
                'cashier_show_bon_number'     => fn () => $table->boolean('cashier_show_bon_number')->default(true),
                'cashier_show_datetime'       => fn () => $table->boolean('cashier_show_datetime')->default(true),
                'cashier_show_seller'         => fn () => $table->boolean('cashier_show_seller')->default(true),
                'cashier_show_order_type'     => fn () => $table->boolean('cashier_show_order_type')->default(true),
                'cashier_show_price_source'   => fn () => $table->boolean('cashier_show_price_source')->default(false),
                'cashier_show_subtotal'       => fn () => $table->boolean('cashier_show_subtotal')->default(true),
                'cashier_show_discount'       => fn () => $table->boolean('cashier_show_discount')->default(true),
                'cashier_show_additional'     => fn () => $table->boolean('cashier_show_additional')->default(true),
                'cashier_show_net'            => fn () => $table->boolean('cashier_show_net')->default(true),
                // صور الهيدر والفوتر
                'cashier_header_image'        => fn () => $table->string('cashier_header_image', 500)->nullable(),
                'cashier_footer_image'        => fn () => $table->string('cashier_footer_image', 500)->nullable(),

                // ── ورقة التحضير: عناصر إضافية ─────────────────────────
                'kitchen_show_order_number'   => fn () => $table->boolean('kitchen_show_order_number')->default(true),
                'kitchen_show_bon_number'     => fn () => $table->boolean('kitchen_show_bon_number')->default(true),
                'kitchen_show_datetime'       => fn () => $table->boolean('kitchen_show_datetime')->default(true),
                'kitchen_show_order_type'     => fn () => $table->boolean('kitchen_show_order_type')->default(true),
                'kitchen_show_price_source'   => fn () => $table->boolean('kitchen_show_price_source')->default(false),
                'kitchen_header_image'        => fn () => $table->string('kitchen_header_image', 500)->nullable(),
                'kitchen_footer_image'        => fn () => $table->string('kitchen_footer_image', 500)->nullable(),
            ];

            foreach ($new as $col => $definition) {
                if (! Schema::hasColumn('settings', $col)) {
                    $definition();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $cols = [
                'cashier_show_order_number', 'cashier_show_bon_number', 'cashier_show_datetime',
                'cashier_show_seller', 'cashier_show_order_type', 'cashier_show_price_source',
                'cashier_show_subtotal', 'cashier_show_discount', 'cashier_show_additional',
                'cashier_show_net', 'cashier_header_image', 'cashier_footer_image',
                'kitchen_show_order_number', 'kitchen_show_bon_number', 'kitchen_show_datetime',
                'kitchen_show_order_type', 'kitchen_show_price_source',
                'kitchen_header_image', 'kitchen_footer_image',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
