# صلاحية "السماح برؤية كل المهمات"

## 📋 الوصف
صلاحية متقدمة تسمح للمستخدم برؤية جميع المهمات في النظام، بدلاً من رؤية المهمات المخصصة له فقط.

## 🔑 معلومات الصلاحية
- **الاسم**: `allow_view_all_tasks`
- **النوع**: `option_type = 2` (خيار متقدم)
- **الفئة**: `tasks_management`
- **الوصف**: "السماح برؤية كل المهمات (تجاوز قيود المستخدم)"

## 📍 مكان الصلاحية
تظهر الصلاحية في:
- صفحة تعديل المستخدم: `/users/{id}/edit`
- تبويب: **"System Options"** (الخيارات)
- القسم: **"tasks_management"**

## 🚀 التفعيل

### 1. تشغيل الـ Seeder
```bash
php artisan db:seed --class=Modules\\Authorization\\Database\\Seeders\\PermissionSelectiveOptionsSeeder
```

### 2. منح الصلاحية للمستخدم
1. اذهب إلى: `/users/{id}/edit`
2. اختر تبويب **"System Options"**
3. ابحث عن **"tasks_management"**
4. فعّل الخيار: **"السماح برؤية كل المهمات"**
5. احفظ التغييرات

## 💡 كيفية العمل

### السلوك الافتراضي (بدون الصلاحية)
المستخدم يرى فقط:
- المهمات المخصصة له (`user_id = auth()->id()`)
- المهمات التي أنشأها (`created_by = auth()->id()`)

### مع الصلاحية
المستخدم يرى **جميع المهمات** في النظام بدون قيود.

## 📂 الملفات المتأثرة

### 1. Seeder
```
Modules/Authorization/Database/Seeders/PermissionSelectiveOptionsSeeder.php
```

### 2. Controller
```
Modules/CRM/Http/Controllers/TaskController.php
```

تم تطبيق الصلاحية في:
- `index()` - قائمة المهمات
- `kanban()` - عرض Kanban
- `timeline()` - عرض Timeline
- `trashed()` - المهمات المحذوفة

## 🔍 مثال الكود

```php
// في TaskController
public function index(Request $request)
{
    $query = Task::with(['client', 'user', 'taskType', 'media', 'creator']);

    // التحقق من الصلاحية
    if (!auth()->user()->can('allow_view_all_tasks')) {
        // المستخدم يرى مهماته فقط
        $query->where(function($q) {
            $q->where('user_id', auth()->id())
              ->orWhere('created_by', auth()->id());
        });
    }
    // إذا كان لديه الصلاحية، يرى كل المهمات

    $tasks = $query->latest('start_date')->paginate(20);
    
    return view('crm::tasks.index', compact('tasks'));
}
```

## ✅ الاختبار

### 1. اختبار بدون الصلاحية
```php
// المستخدم يرى مهماته فقط
$user = User::find(2);
auth()->login($user);

// يجب أن يرى فقط المهمات المخصصة له أو التي أنشأها
$tasks = Task::where(function($q) {
    $q->where('user_id', auth()->id())
      ->orWhere('created_by', auth()->id());
})->get();
```

### 2. اختبار مع الصلاحية
```php
// منح الصلاحية
$user->givePermissionTo('allow_view_all_tasks');

// يجب أن يرى جميع المهمات
$allTasks = Task::all();
```

## 🎯 حالات الاستخدام

### مناسبة لـ:
- ✅ المديرين
- ✅ المشرفين
- ✅ فريق الدعم
- ✅ مراقبي الجودة

### غير مناسبة لـ:
- ❌ الموظفين العاديين
- ❌ المستخدمين الخارجيين
- ❌ العملاء

## 🔒 الأمان

- الصلاحية محمية بنظام Spatie Permissions
- يتم التحقق منها في كل طلب (Request)
- لا يمكن تجاوزها من الـ Frontend
- تعمل مع BranchScope بشكل طبيعي

## 📝 ملاحظات

1. الصلاحية تعمل مع جميع طرق العرض:
   - List View (القائمة)
   - Kanban View (لوحة كانبان)
   - Timeline View (الجدول الزمني)
   - Trashed View (المحذوفات)

2. لا تتعارض مع:
   - صلاحيات CRUD الأساسية (view, create, edit, delete)
   - BranchScope
   - الفلاتر الأخرى (التاريخ، النوع، الحالة، إلخ)

3. يمكن دمجها مع صلاحيات أخرى حسب الحاجة

## 🆘 استكشاف الأخطاء

### المشكلة: الصلاحية لا تظهر في صفحة تعديل المستخدم
**الحل**: تأكد من تشغيل الـ Seeder:
```bash
php artisan db:seed --class=Modules\\Authorization\\Database\\Seeders\\PermissionSelectiveOptionsSeeder
```

### المشكلة: المستخدم لا يرى كل المهمات رغم منحه الصلاحية
**الحل**: 
1. تحقق من أن الصلاحية مفعلة: `$user->hasPermissionTo('allow_view_all_tasks')`
2. امسح الـ cache: `php artisan cache:clear`
3. امسح cache الصلاحيات: `php artisan permission:cache-reset`

### المشكلة: خطأ في الكود
**الحل**: تأكد من أن الكود يستخدم `allow_view_all_tasks` وليس `view all Tasks`

---

**تاريخ الإنشاء**: 2026-03-12  
**الإصدار**: 1.0  
**الحالة**: ✅ مفعّل
