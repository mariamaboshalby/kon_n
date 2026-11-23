# Estate Module - وحدة العقارات

## نظرة عامة
وحدة إدارة العقارات الشاملة لنظام ERP مع دعم كامل لـ API.

## المميزات
- ✅ إدارة العقارات (CRUD)
- ✅ علاقات Many-to-Many مع العملاء والموظفين
- ✅ تصفية متقدمة (السعر، المدينة، عدد الغرف، إلخ)
- ✅ معرض صور للعقارات
- ✅ RESTful API كامل
- ✅ Livewire Volt للواجهة التفاعلية
- ✅ دعم كامل للغة العربية والإنجليزية

## الترجمة (Localization)

الموديول يدعم اللغتين العربية والإنجليزية بشكل كامل:

### ملفات الترجمة:
- `resources/lang/ar/estate.php` - الترجمة العربية
- `resources/lang/en/estate.php` - الترجمة الإنجليزية

### استخدام الترجمة:
```php
// في Blade
{{ __('estate::estate.properties') }}
{{ __('estate::estate.add_property') }}

// في PHP
__('estate::estate.property_created_successfully')
```

### الـ Enums المترجمة:
جميع الـ Enums تدعم الترجمة التلقائية:
```php
ListingType::Sale->label() // "للبيع" أو "For Sale"
PropertyStatus::Available->label() // "متاح" أو "Available"
FinishingLevel::Luxury->label() // "تشطيب فاخر" أو "Luxury"
PaymentMethod::Installments->label() // "تقسيط" أو "Installments"
```

## API Endpoints

### Base URL
```
http://your-domain.com/api/estate
```

### Authentication
جميع الـ endpoints تتطلب Sanctum authentication token:
```
Authorization: Bearer YOUR_TOKEN_HERE
```

### Endpoints

#### 1. Get All Properties (قائمة العقارات)
```http
GET /api/estate/properties
```

**Query Parameters:**
- `search` - البحث في العنوان أو الرمز
- `city` - تصفية حسب المدينة
- `listing_type` - نوع العرض (sale, rent, furnished_rent)
- `status` - الحالة (available, reserved, sold)
- `min_price` - أقل سعر
- `max_price` - أعلى سعر
- `rooms_count` - عدد الغرف
- `per_page` - عدد النتائج في الصفحة (افتراضي: 15)

**Example:**
```bash
curl -X GET "http://your-domain.com/api/estate/properties?city=الرياض&min_price=100000&max_price=500000" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "شقة فاخرة في الرياض",
      "sku": "PROP-12345",
      "listing_type": "sale",
      "status": "available",
      "city": "الرياض",
      "district": "النخيل",
      "price": "450000.00",
      "area": "250.50",
      "rooms_count": 4,
      "bathrooms_count": 3,
      ...
    }
  ],
  "pagination": {
    "total": 50,
    "per_page": 15,
    "current_page": 1,
    "last_page": 4
  }
}
```

#### 2. Get Single Property (عقار واحد)
```http
GET /api/estate/properties/{id}
```

**Example:**
```bash
curl -X GET "http://your-domain.com/api/estate/properties/1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### 3. Create Property (إنشاء عقار)
```http
POST /api/estate/properties
```

**Body (multipart/form-data):**
```json
{
  "title": "شقة فاخرة",
  "sku": "PROP-12345",
  "listing_type": "sale",
  "status": "available",
  "owners": [1, 2],
  "city": "الرياض",
  "district": "النخيل",
  "area": 250.50,
  "rooms_count": 4,
  "bathrooms_count": 3,
  "price": 450000,
  "payment_method": "cash",
  "is_furnished": false,
  "furnishing_percentage": 0,
  "images": [file1, file2]
}
```

#### 4. Update Property (تحديث عقار)
```http
PUT /api/estate/properties/{id}
```

#### 5. Delete Property (حذف عقار)
```http
DELETE /api/estate/properties/{id}
```

#### 6. Get Statistics (الإحصائيات)
```http
GET /api/estate/properties/statistics
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total": 150,
    "available": 120,
    "reserved": 20,
    "sold": 10,
    "for_sale": 80,
    "for_rent": 50,
    "furnished_rent": 20,
    "average_price": 350000,
    "total_value": 52500000
  }
}
```

## Web Interface

### الصفحة الرئيسية
```
http://your-domain.com/estate/properties
```

### المميزات في الواجهة:
- 🔍 بحث وتصفية متقدمة
- 📊 عرض جدولي للعقارات
- 🔗 عرض رابط API مباشرة في الصفحة
- 📋 نسخ رابط API بضغطة واحدة
- ⚡ تحديث فوري باستخدام Livewire
- 🎨 تصميم Bootstrap 5

## Database Schema

### properties
- `id` - معرف العقار
- `title` - العنوان
- `sku` - رمز العقار (فريد)
- `listing_type` - نوع العرض
- `status` - الحالة
- `city`, `district` - الموقع
- `area` - المساحة
- `rooms_count`, `bathrooms_count` - المواصفات
- `price` - السعر
- `payment_method` - طريقة الدفع
- ...

### property_stakeholders (جدول وسيط)
- `property_id` - معرف العقار
- `stakeholder_type` - نوع الطرف (Client, Employee, User)
- `stakeholder_id` - معرف الطرف
- `role` - الدور (owner, tenant, buyer, agent)
- `is_primary` - هل هو الطرف الرئيسي

## Usage Examples

### في Blade:
```blade
@livewire('estate::property-list')
```

### في PHP:
```php
// الحصول على جميع العقارات المتاحة
$properties = Property::available()->get();

// الحصول على المالك الرئيسي
$owner = $property->primaryOwner();

// إضافة مالك
$property->clients()->attach($clientId, [
    'role' => 'owner',
    'is_primary' => true
]);

// الحصول على جميع المستأجرين
$tenants = $property->tenants;
```

## Testing

```bash
php artisan test --filter=PropertyControllerTest
```

## Permissions Required
- `view Properties` - عرض العقارات
- `create Properties` - إنشاء عقار
- `edit Properties` - تعديل عقار
- `delete Properties` - حذف عقار

## Installation

1. تشغيل الـ migrations:
```bash
php artisan migrate --path=Modules/Estate/Database/migrations
```

2. تشغيل الـ seeders (اختياري):
```bash
php artisan db:seed --class=Modules\\Estate\\Database\\Seeders\\EstateDatabaseSeeder
```

## Support
للدعم والاستفسارات، يرجى التواصل مع فريق التطوير.
