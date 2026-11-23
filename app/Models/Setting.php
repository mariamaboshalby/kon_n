<?php

namespace App\Models;

use Modules\Branches\Models\Branch;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'company_name',
        'company_add',
        'company_email',
        'company_tel',
        'edit_pass',
        'lic',
        'updateline',
        'acc_rent',
        'startdate',
        'enddate',
        'lang',
        'bodycolor',
        'showhr',
        'showclinc',
        'showatt',
        'showpayroll',
        'showrent',
        'showpay',
        'showtsk',
        'def_pos_client',
        'def_pos_store',
        'def_pos_employee',
        'def_pos_fund',
        'def_pos_bank',
        'def_pos_price_group',
        'enable_scale_items',
        'scale_code_prefix',
        'scale_code_digits',
        'scale_quantity_digits',
        'scale_quantity_divisor',
        'isdeleted',
        'tenant',
        'branch_id',
        'show_all_tasks',
        'logo',
        'font_family',
        'font_size',
        'restaurant_kitchen_store',
        'restaurant_operating_account',
        'restaurant_sales_account',
        'restaurant_cogs_account',
        'restaurant_inventory_account',
        // ── إعدادات الطباعة العامة ──────────────────────────────────
        'pos_enable_print', 'pos_silent_print',
        // ── فاتورة الكاشير: ورق وخط ─────────────────────────────────
        'cashier_receipt_title', 'cashier_receipt_subtitle', 'cashier_receipt_footer',
        'cashier_paper_width', 'cashier_font_size', 'cashier_separator_style',
        'cashier_header_image', 'cashier_footer_image',
        // ── فاتورة الكاشير: عناصر ───────────────────────────────────
        'cashier_show_logo',
        'cashier_show_order_number',
        'cashier_show_bon_number',
        'cashier_show_datetime',
        'cashier_show_seller',
        'cashier_show_order_type',
        'cashier_show_price_source',
        'cashier_show_table',
        'cashier_show_barcode',
        'cashier_show_notes',
        'cashier_show_subtotal',
        'cashier_show_discount',
        'cashier_show_additional',
        'cashier_show_net',
        'cashier_show_payment_details',
        // ── ورقة التحضير: ورق وخط ───────────────────────────────────
        'kitchen_receipt_title', 'kitchen_receipt_footer',
        'kitchen_paper_width', 'kitchen_font_size', 'kitchen_separator_style', 'kitchen_copies',
        'kitchen_header_image', 'kitchen_footer_image',
        // ── ورقة التحضير: عناصر ─────────────────────────────────────
        'kitchen_show_order_number',
        'kitchen_show_bon_number',
        'kitchen_show_datetime',
        'kitchen_show_table',
        'kitchen_show_cashier_name',
        'kitchen_show_order_type',
        'kitchen_show_price_source',
        'kitchen_show_order_time',
        'kitchen_show_notes',
        'kitchen_show_prices',
        'kitchen_enable_print',
        // ── إعدادات Telegram ─────────────────────────────────────────
        'telegram_bot_token',
        'telegram_chat_id',
    ];

    public $timestamps = false; // لأن الجدول لا يحتوي على created_at أو updated_at

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
