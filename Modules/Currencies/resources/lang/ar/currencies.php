<?php

declare(strict_types=1);

return [
    // General
    'currencies' => 'العملات',
    'currency' => 'عملة',
    'add_currency' => 'إضافة عملة',
    'edit_currency' => 'تعديل العملة',
    'currency_details' => 'تفاصيل العملة',
    'no_currencies' => 'لا توجد عملات',
    'create_new_currency' => 'إنشاء عملة جديدة',
    'manage_currencies' => 'إدارة العملات والأسعار',

    // Fields
    'name' => 'الاسم',
    'name_placeholder' => 'مثال: الدولار الأمريكي',
    'name_required' => 'اسم العملة مطلوب',
    'name_unique' => 'اسم العملة موجود بالفعل',

    'code' => 'الرمز',
    'code_help' => 'رمز العملة الدولي (3 أحرف، مثل: USD, EUR, EGP)',
    'code_required' => 'رمز العملة مطلوب',
    'code_size' => 'يجب أن يكون رمز العملة 3 أحرف بالضبط',
    'code_unique' => 'رمز العملة موجود بالفعل',

    'symbol' => 'الرمز',
    'decimal_places' => 'عدد الأرقام العشرية',
    'decimal_places_required' => 'عدد الأرقام العشرية مطلوب',

    'rate_mode' => 'طريقة السعر',
    'rate_mode_automatic' => 'تلقائي',
    'rate_mode_manual' => 'يدوي',
    'rate_mode_required' => 'طريقة السعر مطلوبة',

    'current_rate' => 'السعر الحالي',
    'exchange_rate_history' => 'سجل أسعار الصرف',
    'date' => 'التاريخ',
    'rate' => 'السعر',
    'showing_latest_rates' => 'عرض آخر :count أسعار',
    'no_exchange_rates' => 'لا توجد أسعار صرف مسجلة لهذه العملة',

    'set_as_default' => 'تعيين كعملة افتراضية',
    'default' => 'افتراضي',

    // Messages
    'created_successfully' => 'تم إنشاء العملة بنجاح',
    'updated_successfully' => 'تم تحديث العملة بنجاح',
    'deleted_successfully' => 'تم حذف العملة بنجاح',
    'rate_updated_successfully' => 'تم تحديث سعر الصرف بنجاح',

    // Errors
    'inactive_currency' => 'لا يمكن استخدام عملة غير مفعلة',
    'invalid_exchange_rate' => 'سعر الصرف غير صحيح',

    // Currency Conversion
    'convert' => 'تحويل العملات',
    'from_currency' => 'من العملة',
    'to_currency' => 'إلى العملة',
    'amount' => 'المبلغ',
    'converted_amount' => 'المبلغ المحول',
    'conversion_rate' => 'سعر التحويل',
    'currency_conversion' => 'تحويل العملة',
    
    // Cash Box Revaluation
    'cash_box_revaluation' => 'إعادة تقييم الصندوق',
    'revaluation_date' => 'تاريخ إعادة التقييم',
    'revalue' => 'إعادة تقييم',
    'revaluation_history' => 'سجل إعادة التقييم',
    'cash_balance' => 'رصيد الصندوق',
    'previous_rate' => 'السعر السابق',
    'rate_change' => 'التغير في السعر',
    'gain_loss' => 'الربح/الخسارة',
    'gain' => 'ربح',
    'loss' => 'خسارة',
    'neutral' => 'محايد',
    'total_gain_loss' => 'إجمالي الربح/الخسارة',
];
