<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * إنشاء جدول العقارات
     */
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            
            // Identity - معلومات التعريف
            $table->string('title');
            $table->string('sku')->unique()->comment('رمز العقار الفريد');
            $table->enum('listing_type', ['sale', 'rent', 'furnished_rent'])->comment('نوع العرض');
            $table->enum('status', ['available', 'reserved', 'sold'])->default('available')->comment('حالة العقار');
            
            // Note: Stakeholders (owners, tenants, buyers) are managed via many-to-many relationships
            // ملاحظة: أطراف العقار (المالكين، المستأجرين، المشترين) تُدار عبر علاقات many-to-many
            
            // Location - الموقع
            $table->string('city')->comment('المدينة');
            $table->string('district')->comment('الحي');
            $table->text('address_details')->nullable()->comment('تفاصيل العنوان');
            $table->decimal('latitude', 10, 8)->nullable()->comment('خط العرض');
            $table->decimal('longitude', 11, 8)->nullable()->comment('خط الطول');
            $table->string('landmark')->nullable()->comment('معلم قريب');
            
            // Technical Specs - المواصفات الفنية
            $table->decimal('area', 10, 2)->comment('المساحة بالمتر المربع');
            $table->integer('floor_number')->nullable()->comment('رقم الطابق');
            $table->integer('rooms_count')->default(0)->comment('عدد الغرف');
            $table->integer('bathrooms_count')->default(0)->comment('عدد دورات المياه');
            $table->enum('finishing_level', ['shell', 'semi_finished', 'finished', 'luxury'])->nullable()->comment('مستوى التشطيب');
            $table->year('build_year')->nullable()->comment('سنة البناء');
            
            // Furnishing & Environment - الأثاث والبيئة المحيطة
            $table->boolean('is_furnished')->default(false)->comment('مفروش');
            $table->integer('furnishing_percentage')->default(0)->comment('نسبة الأثاث 0-100');
            $table->text('neighbor_notes')->nullable()->comment('ملاحظات عن الجيران');
            $table->text('reputation_notes')->nullable()->comment('ملاحظات عن السمعة');
            
            // Financials - المعلومات المالية
            $table->decimal('price', 15, 2)->comment('السعر');
            $table->enum('payment_method', ['cash', 'installments', 'both'])->default('cash')->comment('طريقة الدفع');
            $table->integer('installment_period_months')->nullable()->comment('فترة التقسيط بالأشهر');
            $table->decimal('commission', 15, 2)->nullable()->comment('العمولة');
            
            $table->timestamps();
            
            // Indexes for better performance - فهارس لتحسين الأداء
            $table->index('status');
            $table->index('listing_type');
            $table->index('city');
            $table->index(['price', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
