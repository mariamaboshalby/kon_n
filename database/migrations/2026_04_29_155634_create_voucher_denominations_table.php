<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_denominations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operhead_id')->constrained('operhead')->cascadeOnDelete();
            $table->unsignedInteger('denomination'); // قيمة الفئة: 5, 10, 20, 50, 100, 200
            $table->unsignedInteger('count')->default(0); // عدد الأوراق
            $table->decimal('subtotal', 15, 2)->storedAs('denomination * count'); // إجمالي الفئة
            $table->timestamps();

            $table->index('operhead_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_denominations');
    }
};
