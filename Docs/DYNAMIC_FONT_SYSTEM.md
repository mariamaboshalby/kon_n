# نظام الخطوط الديناميكي - Dynamic Font System

## نظرة عامة
تم إضافة نظام خطوط ديناميكي يسمح بتغيير نوع وحجم الخط في كامل النظام من خلال صفحة إعدادات واحدة.

## الميزات الرئيسية

### 1. اختيار من 10 خطوط عربية مختلفة
- IBM Plex Sans Arabic (الافتراضي)
- Cairo
- Tajawal
- Almarai
- Amiri
- Noto Sans Arabic
- Noto Kufi Arabic
- Changa
- Harmattan
- Lateef

### 2. اختيار حجم الخط
- صغير جداً (12px)
- صغير (14px)
- عادي (16px) - الافتراضي
- كبير (18px)
- كبير جداً (20px)
- ضخم (22px)

### 3. معاينة مباشرة
- معاينة الخط قبل الحفظ
- رؤية كيف سيظهر النص في العناوين والفقرات والجداول

## الوصول للإعدادات
```
URL: http://127.0.0.1:8000/settings/fonts
Route Name: settings.fonts
```

## الملفات المضافة/المعدلة

### 1. Migration
**الملف:** `database/migrations/2026_02_25_132134_add_font_family_to_settings_table.php`

```php
Schema::table('settings', function (Blueprint $table) {
    $table->string('font_family', 100)->default('IBM Plex Sans Arabic')->after('logo');
    $table->string('font_size', 20)->default('16px')->after('font_family');
});
```

### 2. Model
**الملف:** `app/Models/Setting.php`

تم إضافة الحقول الجديدة إلى `$fillable`:
```php
'font_family',
'font_size',
```

### 3. Helper Functions
**الملف:** `app/Helpers/FontHelper.php`

دوال مساعدة للحصول على معلومات الخطوط:
- `get_font_family()` - الحصول على نوع الخط الحالي
- `get_font_size()` - الحصول على حجم الخط الحالي
- `get_available_fonts()` - قائمة الخطوط المتاحة
- `get_font_url()` - رابط Google Fonts للخط الحالي
- `get_font_css_family()` - قيمة CSS font-family
- `get_available_font_sizes()` - قائمة أحجام الخطوط المتاحة

### 4. Livewire Component
**الملف:** `app/Livewire/FontSettings.php`

Component لإدارة إعدادات الخطوط مع:
- تحميل الإعدادات الحالية
- حفظ التغييرات
- معاينة مباشرة
- استعادة الإعدادات الافتراضية

### 5. View
**الملف:** `resources/views/livewire/font-settings.blade.php`

واجهة مستخدم شاملة تحتوي على:
- قائمة منسدلة لاختيار نوع الخط
- قائمة منسدلة لاختيار حجم الخط
- قسم معاينة مباشرة
- معلومات عن الخطوط المتاحة

### 6. Head Template
**الملف:** `resources/views/admin/partials/head.blade.php`

تم تحديث تحميل الخطوط ليكون ديناميكياً:
```blade
<!-- Google Fonts - Dynamic Font Loading -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="{{ get_font_url() }}" rel="stylesheet">

<!-- Dynamic Font Styles -->
<style>
    :root {
        --font-family-primary: {{ get_font_css_family() }};
        --font-size-base: {{ get_font_size() }};
    }
    
    html, body {
        font-family: var(--font-family-primary) !important;
        font-size: var(--font-size-base) !important;
    }
    
    body, h1, h2, h3, h4, h5, h6, p, span, div, a,
    button, input, select, textarea, label,
    th, td, ul, ol, li, table, blockquote, pre, code, strong, em, b, u, small {
        font-family: var(--font-family-primary) !important;
    }
</style>
```

### 7. Routes
**الملف:** `routes/web.php`

```php
Route::get('settings/fonts', \App\Livewire\FontSettings::class)->name('settings.fonts');
```

### 8. Composer Autoload
**الملف:** `composer.json`

تم إضافة FontHelper إلى autoload files:
```json
"files": [
    "app/Helpers/FontHelper.php"
]
```

## كيفية الاستخدام

### للمستخدم النهائي

1. **الوصول للإعدادات:**
   - انتقل إلى `http://127.0.0.1:8000/settings/fonts`

2. **اختيار الخط:**
   - اختر نوع الخط من القائمة المنسدلة
   - اختر حجم الخط المناسب

3. **المعاينة:**
   - شاهد المعاينة المباشرة في قسم "معاينة الخط"
   - تأكد من أن الخط مناسب للقراءة

4. **الحفظ:**
   - انقر على "حفظ التغييرات"
   - سيتم تحديث الصفحة تلقائياً لتطبيق الخط الجديد

5. **استعادة الافتراضي:**
   - انقر على "استعادة الافتراضي" للعودة إلى IBM Plex Sans Arabic بحجم 16px

### للمطورين

#### استخدام Helper Functions

```php
// الحصول على نوع الخط الحالي
$fontFamily = get_font_family();

// الحصول على حجم الخط الحالي
$fontSize = get_font_size();

// الحصول على رابط Google Fonts
$fontUrl = get_font_url();

// الحصول على قيمة CSS
$fontCssFamily = get_font_css_family();

// الحصول على قائمة الخطوط المتاحة
$availableFonts = get_available_fonts();
```

#### إضافة خط جديد

لإضافة خط جديد، عدل دالة `get_available_fonts()` في `app/Helpers/FontHelper.php`:

```php
'Font Name' => [
    'name' => 'Font Display Name',
    'url' => 'https://fonts.googleapis.com/css2?family=Font+Name:wght@...&display=swap',
    'family' => "'Font Name', ui-sans-serif, system-ui, sans-serif",
],
```

## الأداء

### التحسينات
- استخدام `preconnect` لتحميل أسرع من Google Fonts
- تحميل الخط مرة واحدة فقط في head
- استخدام CSS Variables لسهولة التطبيق

### Cache
- الإعدادات محفوظة في قاعدة البيانات
- يتم مسح الـ cache عند الحفظ

## الأمان
- جميع المدخلات يتم التحقق منها (validation)
- الخطوط محملة من Google Fonts (مصدر موثوق)
- استخدام `declare(strict_types=1)` في جميع ملفات PHP

## التوافق
- يعمل مع جميع المتصفحات الحديثة
- يدعم اللغة العربية والإنجليزية
- متوافق مع RTL و LTR

## الاختبار

### اختبار يدوي
1. افتح صفحة الإعدادات
2. جرب كل خط متاح
3. جرب كل حجم متاح
4. تأكد من المعاينة تعمل بشكل صحيح
5. احفظ وتأكد من تطبيق التغييرات

### اختبار الأداء
```bash
# تحقق من عدم وجود أخطاء
php artisan route:list | grep fonts

# تحقق من تحميل الـ helper
composer dump-autoload
```

## الصيانة

### إضافة خط جديد
1. أضف الخط في `get_available_fonts()`
2. تأكد من رابط Google Fonts صحيح
3. اختبر الخط في المعاينة

### تغيير الخط الافتراضي
عدل القيمة الافتراضية في:
- Migration: `->default('New Font Name')`
- Helper: `setting('font_family', 'New Font Name')`

## المشاكل الشائعة

### الخط لا يظهر
- تأكد من اتصال الإنترنت (الخطوط محملة من Google)
- امسح الـ cache: `php artisan cache:clear`
- تأكد من تحديث الصفحة

### التغييرات لا تحفظ
- تأكد من صلاحيات قاعدة البيانات
- تحقق من الـ validation errors
- راجع الـ logs: `storage/logs/laravel.log`

## الدعم الفني
للمساعدة أو الإبلاغ عن مشاكل، راجع:
- ملف التوثيق هذا
- كود المصدر في الملفات المذكورة أعلاه
- Laravel logs

## تاريخ التحديثات
- **2026-02-25:** إصدار أولي - نظام الخطوط الديناميكي
  - 10 خطوط عربية
  - 6 أحجام خطوط
  - معاينة مباشرة
  - واجهة مستخدم سهلة
