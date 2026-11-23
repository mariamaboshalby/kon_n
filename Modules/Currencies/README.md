# Currencies Module

نظام شامل لإدارة العملات وتحويلها وإعادة تقييم الصندوق في نظام massar.

## المميزات الرئيسية

### 1. إدارة العملات (CRUD)
- ✅ إنشاء عملات جديدة
- ✅ تعديل بيانات العملات
- ✅ حذف العملات (مع حماية العملة الافتراضية)
- ✅ عرض تفاصيل العملة وسجل أسعارها
- ✅ تفعيل/إيقاف العملات
- ✅ تعيين عملة افتراضية للنظام

### 2. تحويل العملات
- ✅ تحويل المبالغ بين العملات المختلفة
- ✅ الحصول على سعر الصرف بين عملتين
- ✅ دعم التحويل بناءً على تاريخ محدد
- ✅ API سهل الاستخدام للتطبيقات الأخرى

### 3. إدارة أسعار الصرف
- ✅ تحديث أسعار الصرف يدويًا
- ✅ دعم الأسعار التلقائية واليدوية
- ✅ سجل كامل لأسعار الصرف التاريخية
- ✅ الحصول على أسعار الصرف لفترة زمنية محددة

### 4. إعادة تقييم الصندوق
- ✅ إعادة تقييم أرصدة الصندوق بالعملات المختلفة
- ✅ حساب الأرباح والخسائر من تقلبات أسعار الصرف
- ✅ سجل كامل لعمليات إعادة التقييم
- ✅ تقارير مفصلة عن التغيرات في الأسعار

## البنية المعمارية

```
Modules/Currencies/
├── Http/
│   ├── Controllers/
│   │   ├── CurrenciesController.php          # CRUD للعملات
│   │   └── Api/
│   │       ├── CurrencyConversionController.php    # تحويل العملات
│   │       └── CashBoxRevaluationController.php    # إعادة التقييم
│   └── Requests/
│       ├── StoreCurrencyRequest.php
│       └── UpdateCurrencyRequest.php
├── Services/
│   ├── CurrencyConversionService.php        # منطق التحويل
│   └── CashBoxRevaluationService.php        # منطق إعادة التقييم
├── resources/
│   ├── views/
│   │   ├── index.blade.php                  # قائمة العملات
│   │   ├── create.blade.php                 # إنشاء عملة
│   │   ├── edit.blade.php                   # تعديل عملة
│   │   └── show.blade.php                   # تفاصيل العملة
│   └── lang/
│       ├── ar/currencies.php                # النصوص العربية
│       └── en/currencies.php                # النصوص الإنجليزية
├── routes/
│   ├── web.php                              # المسارات الويب
│   └── api.php                              # مسارات API
├── tests/
│   └── Feature/
│       ├── CurrencyCrudTest.php
│       └── CurrencyConversionTest.php
└── database/
    └── factories/
        └── CurrencyFactory.php
```

## الاستخدام

### 1. إدارة العملات عبر الويب

#### عرض قائمة العملات
```
GET /currencies
```

#### إنشاء عملة جديدة
```
GET /currencies/create          # عرض النموذج
POST /currencies                # حفظ العملة
```

#### تعديل عملة
```
GET /currencies/{id}/edit       # عرض نموذج التعديل
PUT /currencies/{id}            # تحديث العملة
```

#### حذف عملة
```
DELETE /currencies/{id}
```

#### عرض تفاصيل العملة
```
GET /currencies/{id}
```

### 2. تحويل العملات عبر API

#### تحويل مبلغ من عملة إلى أخرى
```bash
POST /api/currencies/convert
Content-Type: application/json

{
    "amount": 100,
    "from_currency_id": 1,
    "to_currency_id": 2,
    "rate_date": "2025-04-14"  // اختياري
}
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

#### الحصول على سعر الصرف
```bash
GET /api/currencies/rate?from_currency_id=1&to_currency_id=2&rate_date=2025-04-14
```

**الرد:**
```json
{
    "success": true,
    "rate": 1.0984
}
```

#### الحصول على العملات النشطة
```bash
GET /api/currencies/active
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
        }
    ]
}
```

#### تحديث سعر الصرف
```bash
POST /api/currencies/rate/update
Content-Type: application/json

{
    "currency_id": 1,
    "rate": 31.5,
    "rate_date": "2025-04-14"
}
```

#### الحصول على سجل أسعار الصرف
```bash
GET /api/currencies/{currency_id}/rate-history?days=30
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
        }
    ]
}
```

### 3. إعادة تقييم الصندوق عبر API

#### إعادة تقييم الصندوق
```bash
POST /api/currencies/revalue
Content-Type: application/json

{
    "revaluation_date": "2025-04-14"  // اختياري، الافتراضي: اليوم
}
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

#### الحصول على سجل إعادة التقييم
```bash
GET /api/currencies/{currency_id}/revaluation-history?days=30
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
            "rate": 31.5,
            "rate_change": 0.3,
            "change_percentage": 0.98
        },
        {
            "date": "2025-04-13",
            "rate": 31.2,
            "rate_change": -0.1,
            "change_percentage": -0.32
        }
    ]
}
```

## استخدام الدوال المساعدة

### في أي مكان في التطبيق

```php
// التحقق من تفعيل نظام تعدد العملات
if (isMultiCurrencyEnabled()) {
    // نظام تعدد العملات مفعل
}

// الحصول على العملة الافتراضية
$defaultCurrency = getDefaultCurrency();

// الحصول على سعر صرف عملة
$rate = getExchangeRate($currencyId);

// تحويل مبلغ من عملة إلى أخرى
$convertedAmount = convertCurrency($amount, $fromCurrencyId, $toCurrencyId);
```

## الاختبارات

### تشغيل الاختبارات
```bash
# تشغيل جميع اختبارات الوحدة
php artisan test tests/Feature/Modules/Currencies/

# تشغيل اختبار محدد
php artisan test tests/Feature/Modules/Currencies/CurrencyCrudTest.php

# تشغيل اختبار محدد بالاسم
php artisan test --filter=test_can_create_currency
```

## نموذج البيانات

### جدول currencies
```sql
CREATE TABLE currencies (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(3) UNIQUE NOT NULL,
    symbol VARCHAR(10),
    decimal_places INT DEFAULT 2,
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    rate_mode ENUM('automatic', 'manual') DEFAULT 'automatic',
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### جدول exchange_rates
```sql
CREATE TABLE exchange_rates (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    currency_id BIGINT NOT NULL,
    rate DECIMAL(18, 8) NOT NULL,
    rate_date DATE NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (currency_id) REFERENCES currencies(id) ON DELETE CASCADE,
    UNIQUE KEY (currency_id, rate_date),
    INDEX (rate_date)
);
```

## الأمان

- ✅ جميع العمليات تتطلب المصادقة
- ✅ التحقق من صحة المدخلات على جانب الخادم
- ✅ حماية من حذف العملة الافتراضية
- ✅ معالجة الأخطاء الشاملة
- ✅ استخدام Transactions للعمليات المعقدة

## الأداء

- ✅ استخدام Eager Loading لتجنب N+1 queries
- ✅ فهرسة قاعدة البيانات على الأعمدة المهمة
- ✅ تخزين مؤقت للعملات النشطة
- ✅ استعلامات محسّنة للتحويل والتقييم

## المتطلبات

- Laravel 12+
- PHP 8.2+
- MySQL 5.7+

## الترخيص

MIT License
