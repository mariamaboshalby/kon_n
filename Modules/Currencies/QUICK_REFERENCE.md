# بطاقة مرجعية سريعة - موديول العملات

## 🔗 المسارات الرئيسية

### الويب
```
GET    /currencies              # قائمة العملات
GET    /currencies/create       # نموذج الإنشاء
POST   /currencies              # حفظ عملة
GET    /currencies/{id}         # تفاصيل العملة
GET    /currencies/{id}/edit    # نموذج التعديل
PUT    /currencies/{id}         # تحديث العملة
DELETE /currencies/{id}         # حذف العملة
```

### API
```
POST   /api/currencies/convert                    # تحويل العملات
GET    /api/currencies/rate                       # سعر الصرف
GET    /api/currencies/active                     # العملات النشطة
POST   /api/currencies/rate/update                # تحديث السعر
GET    /api/currencies/{id}/rate-history          # سجل الأسعار
POST   /api/currencies/revalue                    # إعادة التقييم
GET    /api/currencies/{id}/revaluation-history   # سجل التقييم
```

---

## 📋 أمثلة API

### تحويل العملات
```bash
curl -X POST http://localhost/api/currencies/convert \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 100,
    "from_currency_id": 1,
    "to_currency_id": 2,
    "rate_date": "2025-04-14"
  }'
```

### الحصول على سعر الصرف
```bash
curl -X GET "http://localhost/api/currencies/rate?from_currency_id=1&to_currency_id=2" \
  -H "Authorization: Bearer TOKEN"
```

### الحصول على العملات النشطة
```bash
curl -X GET http://localhost/api/currencies/active \
  -H "Authorization: Bearer TOKEN"
```

### تحديث سعر الصرف
```bash
curl -X POST http://localhost/api/currencies/rate/update \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "currency_id": 1,
    "rate": 31.5,
    "rate_date": "2025-04-14"
  }'
```

### إعادة تقييم الصندوق
```bash
curl -X POST http://localhost/api/currencies/revalue \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "revaluation_date": "2025-04-14"
  }'
```

### الحصول على سجل الأسعار
```bash
curl -X GET "http://localhost/api/currencies/1/rate-history?days=30" \
  -H "Authorization: Bearer TOKEN"
```

---

## 🔧 الدوال المساعدة

```php
// التحقق من تفعيل تعدد العملات
isMultiCurrencyEnabled()

// الحصول على العملة الافتراضية
getDefaultCurrency()

// الحصول على سعر صرف عملة
getExchangeRate($currencyId)

// تحويل مبلغ من عملة إلى أخرى
convertCurrency($amount, $fromCurrencyId, $toCurrencyId)
```

---

## 📊 نموذج البيانات

### جدول currencies
```sql
id              BIGINT PRIMARY KEY
name            VARCHAR(255)
code            VARCHAR(3) UNIQUE
symbol          VARCHAR(10)
decimal_places  INT
is_default      BOOLEAN
is_active       BOOLEAN
rate_mode       ENUM('automatic', 'manual')
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### جدول exchange_rates
```sql
id              BIGINT PRIMARY KEY
currency_id     BIGINT FOREIGN KEY
rate            DECIMAL(18, 8)
rate_date       DATE
created_at      TIMESTAMP
updated_at      TIMESTAMP
UNIQUE(currency_id, rate_date)
```

---

## 🧪 الاختبارات

```bash
# جميع الاختبارات
php artisan test Modules/Currencies/tests/

# اختبارات CRUD
php artisan test Modules/Currencies/tests/Feature/CurrencyCrudTest.php

# اختبارات التحويل
php artisan test Modules/Currencies/tests/Feature/CurrencyConversionTest.php

# اختبار محدد
php artisan test --filter=test_can_create_currency
```

---

## 📝 الترجمة

### المفاتيح الرئيسية
```php
__('currencies.currencies')              // العملات
__('currencies.add_currency')            // إضافة عملة
__('currencies.name')                    // الاسم
__('currencies.code')                    // الرمز
__('currencies.symbol')                  // الرمز
__('currencies.decimal_places')          // عدد الأرقام العشرية
__('currencies.rate_mode')               // طريقة السعر
__('currencies.current_rate')            // السعر الحالي
__('currencies.exchange_rate_history')   // سجل أسعار الصرف
__('currencies.cash_box_revaluation')    // إعادة تقييم الصندوق
__('currencies.created_successfully')    // تم الإنشاء بنجاح
__('currencies.updated_successfully')    // تم التحديث بنجاح
__('currencies.deleted_successfully')    // تم الحذف بنجاح
```

---

## 🔐 الأمان

- ✅ جميع العمليات تتطلب المصادقة
- ✅ API endpoints تتطلب Bearer Token
- ✅ منع حذف العملة الافتراضية
- ✅ التحقق من صحة المدخلات
- ✅ معالجة الأخطاء الشاملة

---

## 📁 هيكل الملفات

```
Modules/Currencies/
├── Http/Controllers/
│   ├── CurrenciesController.php
│   └── Api/
│       ├── CurrencyConversionController.php
│       └── CashBoxRevaluationController.php
├── Http/Requests/
│   ├── StoreCurrencyRequest.php
│   └── UpdateCurrencyRequest.php
├── Services/
│   ├── CurrencyConversionService.php
│   └── CashBoxRevaluationService.php
├── resources/views/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
├── resources/lang/
│   ├── ar/currencies.php
│   └── en/currencies.php
├── routes/
│   ├── web.php
│   └── api.php
├── tests/Feature/
│   ├── CurrencyCrudTest.php
│   └── CurrencyConversionTest.php
└── database/factories/
    └── CurrencyFactory.php
```

---

## 🚀 البدء السريع

### 1. الوصول إلى الموديول
```
http://your-domain.com/currencies
```

### 2. إضافة عملة
- اضغط "إضافة عملة"
- ملء البيانات
- اضغط "حفظ"

### 3. تحديث السعر
```bash
curl -X POST http://localhost/api/currencies/rate/update \
  -H "Authorization: Bearer TOKEN" \
  -d '{
    "currency_id": 1,
    "rate": 31.5,
    "rate_date": "2025-04-14"
  }'
```

### 4. تحويل العملات
```bash
curl -X POST http://localhost/api/currencies/convert \
  -H "Authorization: Bearer TOKEN" \
  -d '{
    "amount": 100,
    "from_currency_id": 1,
    "to_currency_id": 2
  }'
```

---

## 💡 نصائح

1. **تحديث الأسعار**: حدّث الأسعار يوميًا
2. **إعادة التقييم**: قم بها شهريًا
3. **النسخ الاحتياطية**: احتفظ بسجل كامل
4. **المراقبة**: راقب الأرباح والخسائر

---

## 🐛 استكشاف الأخطاء

| المشكلة | السبب | الحل |
|--------|------|------|
| لا يمكن حذف العملة | عملة افتراضية | عيّن عملة أخرى كافتراضية أولاً |
| سعر غير صحيح | لا توجد أسعار | حدّث الأسعار |
| عملة معطلة | العملة غير مفعلة | فعّل العملة |

---

## 📞 الدعم

للمساعدة، راجع:
- [README.md](./README.md) - التوثيق الكامل
- [GUIDE.md](./GUIDE.md) - دليل الاستخدام
- [IMPLEMENTATION_SUMMARY.md](./IMPLEMENTATION_SUMMARY.md) - ملخص التنفيذ

---

**آخر تحديث**: 14 أبريل 2025
