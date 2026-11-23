# نموذج عرض فاتورة التصنيع

## الوصف
نموذج لعرض تفاصيل فاتورة التصنيع المرتبطة بأمر إنتاج، يعرض المنتجات المصنعة والمواد الخام المستخدمة مع التكاليف.

## الملفات المنشأة

### 1. Volt Component
**المسار:** `resources/views/livewire/production-orders-management/show-invoice.blade.php`

يحتوي على:
- منطق PHP لتحميل بيانات الفاتورة
- عرض معلومات الفاتورة الأساسية
- جدول المنتجات المصنعة
- جدول المواد الخام المستخدمة
- ملخص التكاليف
- دعم الطباعة

### 2. View Template
**المسار:** `resources/views/manufacturing/production-orders/show-invoice.blade.php`

يحتوي على:
- Layout الصفحة
- Breadcrumb navigation
- استدعاء Volt component

### 3. Controller Method
**المسار:** `app/Http/Controllers/ProductionOrderController.php`

تم إضافة method جديد:
```php
public function showInvoice(int $invoiceId)
```

### 4. Route
**المسار:** `routes/web.php`

```php
Route::get('production-orders/invoice/{invoiceId}', [ProductionOrderController::class, 'showInvoice'])
    ->name('production-orders.show-invoice');
```

## مفاتيح الترجمة المضافة

### في `lang/ar/items.php`:
- `manufacturing_invoice_details` - تفاصيل فاتورة التصنيع
- `manufactured_products` - المنتجات المصنعة
- `raw_materials` - المواد الخام
- `material_name` - اسم المادة
- `product_name` - اسم المنتج
- `unit_cost` - تكلفة الوحدة
- `total_products_value` - إجمالي قيمة المنتجات
- `total_raw_materials` - إجمالي المواد الخام
- `total_invoice_cost` - إجمالي تكلفة الفاتورة

### في `resources/lang/ar/common.php`:
- `invoice_information` - معلومات الفاتورة
- `invoice_number` - رقم الفاتورة
- `serial_number` - الرقم التسلسلي
- `additional_information` - معلومات إضافية
- `production_order` - أمر الإنتاج
- `employee` - الموظف
- `branch` - الفرع
- `products_account` - حساب المنتجات
- `raw_materials_account` - حساب المواد الخام
- `unknown` - غير معروف

## كيفية الاستخدام

### 1. من صفحة عرض أمر الإنتاج
عند عرض أمر إنتاج مرتبط بفاتورة تصنيع، سيظهر زر "عرض تفاصيل فاتورة التصنيع" في قسم المعلومات الأساسية.

### 2. الوصول المباشر
```
/production-orders/invoice/{invoiceId}
```

### 3. من الكود
```php
route('production-orders.show-invoice', $invoiceId)
```

## المميزات

### 1. عرض شامل
- معلومات الفاتورة الأساسية (رقم، تاريخ، وصف)
- معلومات أمر الإنتاج المرتبط
- معلومات الموظف والفرع
- الحسابات المحاسبية المرتبطة

### 2. جداول تفصيلية
- جدول المنتجات المصنعة مع الكميات والتكاليف
- جدول المواد الخام المستخدمة
- حساب الإجماليات تلقائياً

### 3. ملخص مرئي
- بطاقات ملونة لعرض الإجماليات
- أيقونات توضيحية
- تنسيق احترافي

### 4. دعم الطباعة
- زر طباعة في الواجهة
- تنسيق خاص للطباعة
- إخفاء العناصر غير الضرورية عند الطباعة

## البنية التقنية

### Alpine.js vs Livewire
- تم استخدام Livewire فقط لتحميل البيانات من السيرفر
- لا توجد تفاعلات معقدة تحتاج Alpine.js
- زر الطباعة يستخدم JavaScript عادي

### Bootstrap 5
- استخدام classes Bootstrap للتنسيق
- Cards للتقسيم المنطقي
- Tables responsive للجداول
- Grid system للتخطيط

### Best Practices
- Type hints في جميع الـ methods
- Eager loading للعلاقات
- Localization لجميع النصوص
- Clean code structure
- Separation of concerns

## التخصيص

### تعديل الألوان
يمكن تعديل ألوان البطاقات في section الملخص:
```blade
<div class="card border-success">  <!-- للمنتجات -->
<div class="card border-info">     <!-- للمواد الخام -->
<div class="card border-primary">  <!-- للإجمالي -->
```

### إضافة حقول جديدة
1. أضف الحقل في method `loadInvoiceData()`
2. أضف عرض الحقل في template
3. أضف مفتاح الترجمة إذا لزم

### تعديل التنسيق للطباعة
عدّل في section `@push('styles')`:
```css
@media print {
    /* قواعد CSS للطباعة */
}
```

## الأمان

- استخدام `findOrFail()` للتحقق من وجود الفاتورة
- Eager loading لمنع N+1 queries
- Type safety مع `declare(strict_types=1)`
- Authorization يمكن إضافتها في Controller

## الأداء

- Eager loading لجميع العلاقات المطلوبة
- حساب الإجماليات في PHP بدلاً من queries إضافية
- Caching يمكن إضافته للفواتير الكبيرة

## الصيانة

### إضافة قسم جديد
1. أضف البيانات في `loadInvoiceData()`
2. أضف HTML في template
3. أضف مفاتيح الترجمة
4. اختبر الطباعة

### تحديث التصميم
- جميع الـ styles في `@push('styles')`
- استخدم Bootstrap classes قدر الإمكان
- حافظ على التناسق مع باقي النظام
