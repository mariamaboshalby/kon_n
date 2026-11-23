# Estate Module - Translation Guide
# دليل الترجمة - موديول العقارات

## Overview | نظرة عامة

This module is fully localized in both Arabic and English. All user-facing text uses Laravel's translation system.

هذا الموديول مترجم بالكامل للعربية والإنجليزية. جميع النصوص المعروضة للمستخدم تستخدم نظام الترجمة في Laravel.

---

## Translation Files | ملفات الترجمة

### Location | الموقع
```
Modules/Estate/resources/lang/
├── ar/
│   └── estate.php  (Arabic translations)
└── en/
    └── estate.php  (English translations)
```

### Structure | البنية

The translation files are organized into logical sections:

ملفات الترجمة منظمة في أقسام منطقية:

1. **Module Info** - معلومات الموديول
2. **Actions** - الإجراءات
3. **Listing Types** - أنواع العروض
4. **Status** - الحالات
5. **Finishing Levels** - مستويات التشطيب
6. **Payment Methods** - طرق الدفع
7. **Fields** - الحقول
8. **Filters** - الفلاتر
9. **Messages** - الرسائل
10. **Validation Messages** - رسائل التحقق
11. **Stakeholder Roles** - أدوار الأطراف
12. **API & UI** - واجهة المستخدم والـ API

---

## Usage Examples | أمثلة الاستخدام

### In Blade Templates | في قوالب Blade

```blade
{{-- Simple translation --}}
<h1>{{ __('estate::estate.properties') }}</h1>

{{-- Button with icon --}}
<button>
    <i class="fas fa-plus"></i>
    {{ __('estate::estate.add_property') }}
</button>

{{-- Form label --}}
<label>{{ __('estate::estate.title') }}</label>

{{-- Validation error --}}
@error('title')
    <span class="error">{{ __('estate::estate.validation_title_required') }}</span>
@enderror
```

### In PHP Controllers | في الـ Controllers

```php
// Success message
return redirect()->back()
    ->with('message', __('estate::estate.property_created_successfully'));

// Error message
return redirect()->back()
    ->with('error', __('estate::estate.property_creation_failed'));

// API response
return response()->json([
    'message' => __('estate::estate.property_not_found')
], 404);
```

### In Form Requests | في طلبات النماذج

```php
public function messages(): array
{
    return [
        'title.required' => __('estate::estate.validation_title_required'),
        'sku.unique' => __('estate::estate.validation_sku_unique'),
        'price.min' => __('estate::estate.validation_price_min'),
    ];
}
```

### In Enums | في الـ Enums

All enums have a `label()` method that returns the translated label:

جميع الـ Enums لديها دالة `label()` تُرجع التسمية المترجمة:

```php
use Modules\Estate\Enums\ListingType;
use Modules\Estate\Enums\PropertyStatus;

// Get translated label
$label = ListingType::Sale->label(); // "للبيع" or "For Sale"
$status = PropertyStatus::Available->label(); // "متاح" or "Available"

// In Blade
<span>{{ $property->listing_type->label() }}</span>
<span>{{ $property->status->label() }}</span>
```

---

## Available Enums | الـ Enums المتاحة

### 1. ListingType | نوع العرض
```php
ListingType::Sale           // للبيع | For Sale
ListingType::Rent           // للإيجار | For Rent
ListingType::FurnishedRent  // إيجار مفروش | Furnished Rent
```

### 2. PropertyStatus | حالة العقار
```php
PropertyStatus::Available   // متاح | Available
PropertyStatus::Reserved    // محجوز | Reserved
PropertyStatus::Sold        // مباع | Sold
```

### 3. FinishingLevel | مستوى التشطيب
```php
FinishingLevel::Shell         // عظم | Shell
FinishingLevel::SemiFinished  // نصف تشطيب | Semi-Finished
FinishingLevel::Finished      // تشطيب كامل | Finished
FinishingLevel::Luxury        // تشطيب فاخر | Luxury
```

### 4. PaymentMethod | طريقة الدفع
```php
PaymentMethod::Cash          // نقدي | Cash
PaymentMethod::Installments  // تقسيط | Installments
PaymentMethod::Both          // نقدي أو تقسيط | Cash or Installments
```

---

## Adding New Translations | إضافة ترجمات جديدة

### Step 1: Add to Translation Files | الخطوة 1: إضافة للملفات

**Arabic (ar/estate.php):**
```php
'new_key' => 'النص العربي',
```

**English (en/estate.php):**
```php
'new_key' => 'English Text',
```

### Step 2: Use in Code | الخطوة 2: الاستخدام في الكود

```php
__('estate::estate.new_key')
```

---

## Translation Keys Reference | مرجع مفاتيح الترجمة

### Common Keys | المفاتيح الشائعة

| Key | Arabic | English |
|-----|--------|---------|
| `properties` | العقارات | Properties |
| `add_property` | إضافة عقار | Add Property |
| `edit_property` | تعديل عقار | Edit Property |
| `delete_property` | حذف عقار | Delete Property |
| `search` | بحث | Search |
| `filter_properties` | تصفية العقارات | Filter Properties |
| `reset_filters` | إعادة تعيين الفلاتر | Reset Filters |
| `all` | الكل | All |

### Field Keys | مفاتيح الحقول

| Key | Arabic | English |
|-----|--------|---------|
| `title` | العنوان | Title |
| `sku` | رمز العقار | Property Code |
| `city` | المدينة | City |
| `district` | الحي | District |
| `area` | المساحة (م²) | Area (m²) |
| `price` | السعر | Price |
| `rooms_count` | عدد الغرف | Number of Rooms |
| `bathrooms_count` | عدد دورات المياه | Number of Bathrooms |

### Message Keys | مفاتيح الرسائل

| Key | Arabic | English |
|-----|--------|---------|
| `property_created_successfully` | تم إنشاء العقار بنجاح | Property created successfully |
| `property_updated_successfully` | تم تحديث العقار بنجاح | Property updated successfully |
| `property_deleted_successfully` | تم حذف العقار بنجاح | Property deleted successfully |
| `no_properties_found` | لا توجد عقارات | No properties found |

---

## Best Practices | أفضل الممارسات

### 1. Never Hardcode Text | لا تكتب النصوص مباشرة

❌ **Bad:**
```blade
<h1>Properties</h1>
<button>Add Property</button>
```

✅ **Good:**
```blade
<h1>{{ __('estate::estate.properties') }}</h1>
<button>{{ __('estate::estate.add_property') }}</button>
```

### 2. Use Descriptive Keys | استخدم مفاتيح وصفية

❌ **Bad:**
```php
'msg1' => 'Success',
'btn1' => 'Click here',
```

✅ **Good:**
```php
'property_created_successfully' => 'Property created successfully',
'add_property' => 'Add Property',
```

### 3. Group Related Keys | جمّع المفاتيح المتعلقة

```php
// Validation messages together
'validation_title_required' => '...',
'validation_sku_required' => '...',
'validation_price_required' => '...',

// Status messages together
'status_available' => '...',
'status_reserved' => '...',
'status_sold' => '...',
```

### 4. Use Enum Labels | استخدم تسميات الـ Enums

❌ **Bad:**
```blade
@if($property->status === 'available')
    <span>Available</span>
@endif
```

✅ **Good:**
```blade
<span>{{ $property->status->label() }}</span>
```

---

## Testing Translations | اختبار الترجمات

### Change Language | تغيير اللغة

```php
// In controller or middleware
app()->setLocale('ar'); // Arabic
app()->setLocale('en'); // English

// In .env file
APP_LOCALE=ar
APP_FALLBACK_LOCALE=en
```

### Verify All Keys Exist | التحقق من وجود جميع المفاتيح

```bash
# Run this command to check for missing translations
php artisan lang:check estate
```

---

## Support | الدعم

For questions about translations or to report missing translations:

للأسئلة حول الترجمات أو للإبلاغ عن ترجمات مفقودة:

- Check the translation files first
- Ensure you're using the correct key format: `estate::estate.key_name`
- Contact the development team for assistance

---

**Last Updated:** 2025-04-20
**Version:** 1.0.0
