<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * يضيف عمودين لجدول journal_details لدعم العملات الأجنبية:
 *
 * - foreign_amount      : المبلغ بعملة الحساب الأجنبية (مثلاً 10 دولار)
 * - foreign_currency_id : معرّف العملة الأجنبية (FK → currencies)
 *
 * القيد المحاسبي (debit/credit) يبقى دايماً بالعملة الأساسية للنظام.
 * لو الحساب بالعملة الأساسية → foreign_amount = null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_details', function (Blueprint $table): void {
            // المبلغ بعملة الحساب الأجنبية (null = عملة أساسية)
            $table->decimal('foreign_amount', 15, 4)->nullable()->after('credit');

            // العملة الأجنبية المستخدمة
            $table->unsignedBigInteger('foreign_currency_id')->nullable()->after('foreign_amount');

            $table->foreign('foreign_currency_id')
                ->references('id')
                ->on('currencies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('journal_details', function (Blueprint $table): void {
            $table->dropForeign(['foreign_currency_id']);
            $table->dropColumn(['foreign_amount', 'foreign_currency_id']);
        });
    }
};
