# دليل استخدام موديول العملات

## 📋 نظرة عامة

موديول شامل لإدارة العملات وتحويلها وإعادة تقييم الصندوق في نظام massar. يوفر:

- ✅ إدارة كاملة للعملات (CRUD)
- ✅ تحويل العملات بين بعضها
- ✅ إدارة أسعار الصرف
- ✅ إعادة تقييم الصندوق والحسابات
- ✅ API سهل الاستخدام
- ✅ تقارير مفصلة

---

## 🚀 البدء السريع

### 1. الوصول إلى قائمة العملات

```
http://your-domain.com/currencies
```

### 2. إضافة عملة جديدة

1. اضغط على زر "إضافة عملة"
2. ملء البيانات:
   - **الاسم**: مثل "الدولار الأمريكي"
   - **الرمز**: 3 أحرف (USD, EUR, EGP)
   - **الرمز**: $ أو € أو ج.م
   - **عدد الأرقام العشرية**: عادة 2
   - **طريقة السعر**: تلقائي أو يدوي
   - **تفعيل**: تفعيل العملة
   - **تعيين كافتراضية**: إذا كانت العملة الأساسية

3. اضغط "حفظ"

### 3. تحديث سعر الصرف

1. من قائمة العملات، اضغط على العملة
2. في صفحة التفاصيل، ستجد سجل الأسعار
3. لتحديث السعر، استخدم API:

```bash
curl -X POST http://your-domain.com/api/currencies/rate/update \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "currency_id": 1,
    "rate": 31.5,
    "rate_date": "2025-04-14"
  }'
```

---

## 💱 تحويل العملات

### عبر الويب (واجهة المستخدم)

حاليًا يتم التحويل عبر API. يمكنك إنشاء صفحة ويب للتحويل باستخدام الـ API.

### عبر API

#### مثال 1: تحويل 100 دولار إلى يورو

```bash
curl -X POST http://your-domain.com/api/currencies/convert \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 100,
    "from_currency_id": 1,
    "to_currency_id": 2
  }'
```

**الرد:**
```json
{
  "success": true,
  "data": {
    "original_amount": 100,
    "converted_amount": 109.84,
    "from_currency": {
      "id": 1,
      "name": "US Dollar",
      "code": "USD",
      "symbol": "$",
      "decimal_places": 2
    },
    "to_currency": {
      "id": 2,
      "name": "Euro",
      "code": "EUR",
      "symbol": "€",
      "decimal_places": 2
    },
    "rate": 1.0984,
    "rate_date": "2025-04-14"
  }
}
```

#### مثال 2: الحصول على سعر الصرف فقط

```bash
curl -X GET "http://your-domain.com/api/currencies/rate?from_currency_id=1&to_currency_id=2" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**الرد:**
```json
{
  "success": true,
  "rate": 1.0984
}
```

#### مثال 3: الحصول على العملات النشطة

```bash
curl -X GET http://your-domain.com/api/currencies/active \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**الرد:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "US Dollar",
      "code": "USD",
      "symbol": "$",
      "is_default": false,
      "rate": 30.5
    },
    {
      "id": 2,
      "name": "Euro",
      "code": "EUR",
      "symbol": "€",
      "is_default": false,
      "rate": 33.5
    },
    {
      "id": 3,
      "name": "Egyptian Pound",
      "code": "EGP",
      "symbol": "ج.م",
      "is_default": true,
      "rate": 1
    }
  ]
}
```

---

## 📊 إعادة تقييم الصندوق

### ما هي إعادة التقييم؟

عند تغير أسعار الصرف، تتغير قيمة الأموال المحتفظ بها بعملات أجنبية. إعادة التقييم تحسب:

- الربح أو الخسارة من تغير الأسعار
- الفرق بين السعر السابق والحالي
- إجمالي التأثير على الصندوق

### مثال عملي

لديك:
- 1000 دولار أمريكي
- السعر السابق: 30.2 جنيه/دولار
- السعر الحالي: 30.5 جنيه/دولار

**الحساب:**
- الربح = 1000 × (30.5 - 30.2) = 1000 × 0.3 = 300 جنيه

### عبر API

```bash
curl -X POST http://your-domain.com/api/currencies/revalue \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "revaluation_date": "2025-04-14"
  }'
```

**الرد:**
```json
{
  "success": true,
  "revaluation_date": "2025-04-14",
  "default_currency": {
    "id": 3,
    "name": "Egyptian Pound",
    "code": "EGP"
  },
  "revaluations": [
    {
      "currency_id": 1,
      "currency_name": "US Dollar",
      "currency_code": "USD",
      "cash_balance": 1000,
      "previous_rate": 30.2,
      "current_rate": 30.5,
      "rate_change": 0.3,
      "gain_loss": 300,
      "gain_loss_type": "gain"
    },
    {
      "currency_id": 2,
      "currency_name": "Euro",
      "currency_code": "EUR",
      "cash_balance": 500,
      "previous_rate": 33.2,
      "current_rate": 33.5,
      "rate_change": 0.3,
      "gain_loss": 150,
      "gain_loss_type": "gain"
    }
  ],
  "total_gain_loss": 450
}
```

---

## 🔧 استخدام الدوال المساعدة

### في أي مكان في التطبيق

```php
<?php

// 1. التحقق من تفعيل نظام تعدد العملات
if (isMultiCurrencyEnabled()) {
    echo "نظام تعدد العملات مفعل";
}

// 2. الحصول على العملة الافتراضية
$defaultCurrency = getDefaultCurrency();
echo $defaultCurrency->name; // "Egyptian Pound"

// 3. الحصول على سعر صرف عملة
$rate = getExchangeRate(1); // سعر الدولار
echo $rate; // 30.5

// 4. تحويل مبلغ من عملة إلى أخرى
$amount = 100; // 100 دولار
$converted = convertCurrency($amount, 1, 2); // تحويل من دولار إلى يورو
echo $converted; // 109.84
```

### في Blade Template

```blade
<!-- عرض سعر العملة -->
<p>سعر الدولار: {{ getExchangeRate(1) }}</p>

<!-- تحويل مبلغ -->
<p>100 دولار = {{ convertCurrency(100, 1, 2) }} يورو</p>

<!-- التحقق من تفعيل تعدد العملات -->
@if (isMultiCurrencyEnabled())
    <p>نظام تعدد العملات مفعل</p>
@endif
```

---

## 📈 سجل أسعار الصرف

### الحصول على سجل الأسعار

```bash
curl -X GET "http://your-domain.com/api/currencies/1/rate-history?days=30" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**الرد:**
```json
{
  "success": true,
  "currency": {
    "id": 1,
    "name": "US Dollar",
    "code": "USD"
  },
  "history": [
    {
      "date": "2025-04-14",
      "rate": 31.5
    },
    {
      "date": "2025-04-13",
      "rate": 31.2
    },
    {
      "date": "2025-04-12",
      "rate": 31.0
    }
  ]
}
```

---

## 🔐 الأمان والصلاحيات

### المتطلبات

- جميع العمليات تتطلب المصادقة (تسجيل الدخول)
- API endpoints تتطلب token (Bearer Token)

### الحماية

- ✅ لا يمكن حذف العملة الافتراضية
- ✅ لا يمكن إلغاء الـ default عن العملة الافتراضية إذا كانت الوحيدة
- ✅ التحقق من صحة المدخلات على جانب الخادم
- ✅ معالجة الأخطاء الشاملة

---

## 🧪 الاختبارات

### تشغيل الاختبارات

```bash
# جميع اختبارات الموديول
php artisan test Modules/Currencies/tests/

# اختبارات CRUD فقط
php artisan test Modules/Currencies/tests/Feature/CurrencyCrudTest.php

# اختبارات التحويل فقط
php artisan test Modules/Currencies/tests/Feature/CurrencyConversionTest.php

# اختبار محدد
php artisan test --filter=test_can_create_currency
```

---

## 📝 أمثلة عملية

### مثال 1: إنشاء نظام تحويل عملات في الفاتورة

```php
<?php

namespace Modules\Invoices\Services;

use Modules\Currencies\Services\CurrencyConversionService;

class InvoiceConversionService
{
    public function __construct(
        private CurrencyConversionService $conversionService
    ) {}

    public function convertInvoiceTotal(
        float $total,
        int $fromCurrencyId,
        int $toCurrencyId
    ): float {
        $result = $this->conversionService->convert(
            $total,
            $fromCurrencyId,
            $toCurrencyId
        );

        return $result['converted_amount'];
    }
}
```

### مثال 2: عرض الأسعار في Dashboard

```blade
<!-- resources/views/dashboard.blade.php -->

<div class="row">
    @foreach (getActiveCurrencies() as $currency)
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ $currency->name }}</h5>
                    <p class="card-text">
                        <strong>{{ $currency->code }}</strong>
                        <span class="badge bg-primary">{{ $currency->latestRate->rate ?? 1 }}</span>
                    </p>
                </div>
            </div>
        </div>
    @endforeach
</div>
```

### مثال 3: تحديث الأسعار تلقائيًا

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Settings\Models\Currency;
use Modules\Settings\Models\ExchangeRate;

class UpdateExchangeRates extends Command
{
    protected $signature = 'currencies:update-rates';
    protected $description = 'Update exchange rates from external API';

    public function handle(): int
    {
        $currencies = Currency::where('rate_mode', 'automatic')
            ->where('is_active', true)
            ->get();

        foreach ($currencies as $currency) {
            // احصل على السعر من API خارجي
            $rate = $this->fetchRateFromExternalAPI($currency->code);

            if ($rate) {
                ExchangeRate::create([
                    'currency_id' => $currency->id,
                    'rate' => $rate,
                    'rate_date' => now()->format('Y-m-d'),
                ]);

                $this->info("Updated {$currency->code}: {$rate}");
            }
        }

        return self::SUCCESS;
    }

    private function fetchRateFromExternalAPI(string $code): ?float
    {
        // استدعاء API خارجي (مثل OpenExchangeRates)
        // وإرجاع السعر
        return null;
    }
}
```

---

## 🐛 استكشاف الأخطاء

### المشكلة: "لا يمكن حذف العملة الافتراضية"

**السبب:** محاولة حذف العملة المعينة كافتراضية

**الحل:** 
1. عيّن عملة أخرى كافتراضية أولاً
2. ثم احذف العملة القديمة

### المشكلة: "سعر الصرف غير صحيح"

**السبب:** لا توجد أسعار صرف مسجلة للعملة

**الحل:**
1. تأكد من تحديث أسعار الصرف
2. استخدم API لتحديث الأسعار

### المشكلة: "العملة غير مفعلة"

**السبب:** محاولة استخدام عملة معطلة

**الحل:**
1. اذهب إلى تفاصيل العملة
2. فعّل العملة
3. حاول مرة أخرى

---

## 📚 مراجع إضافية

- [README.md](./README.md) - التوثيق الكامل
- [API Documentation](./README.md#الاستخدام) - توثيق API
- [Database Schema](./README.md#نموذج-البيانات) - هيكل قاعدة البيانات

---

## 💡 نصائح مهمة

1. **تحديث الأسعار بانتظام**: حدّث أسعار الصرف يوميًا على الأقل
2. **النسخ الاحتياطية**: احتفظ بسجل كامل للأسعار التاريخية
3. **التحقق من الأسعار**: تأكد من صحة الأسعار قبل الاستخدام
4. **إعادة التقييم الدورية**: قم بإعادة تقييم الصندوق شهريًا
5. **المراقبة**: راقب الأرباح والخسائر من تقلبات الأسعار

---

## 📞 الدعم

للمساعدة أو الإبلاغ عن مشاكل، يرجى التواصل مع فريق التطوير.
