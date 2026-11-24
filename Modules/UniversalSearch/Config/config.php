<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Universal Search - Route Labels
    |--------------------------------------------------------------------------
    |
    | Optional human labels per route name to show in results.
    | Example:
    | 'labels' => [
    |   'admin.dashboard' => ['title' => 'Dashboard', 'group' => 'Admin'],
    | ],
    */
    'labels' => [

        // ── الحسابات ──────────────────────────────────────────────
        'accounts.index'                    => ['title' => 'دليل الحسابات', 'group' => 'الحسابات'],
        'accounts.startBalance'             => ['title' => 'أرصدة أول المدة', 'group' => 'الحسابات'],
        'accounts.balanceSheet'             => ['title' => 'الميزانية العمومية', 'group' => 'الحسابات'],
        'accounts.basic-data-statistics'    => ['title' => 'إحصائيات البيانات الأساسية', 'group' => 'الحسابات'],

        // ── الفواتير ──────────────────────────────────────────────
        'invoices.index'                    => ['title' => 'قائمة الفواتير', 'group' => 'الفواتير'],
        'invoices.form.create'              => ['title' => 'إضافة فاتورة جديدة', 'group' => 'الفواتير'],
        'invoices.requests.index'           => ['title' => 'طلبات الفواتير', 'group' => 'الفواتير'],
        'invoices.track.search'             => ['title' => 'تتبع الفواتير', 'group' => 'الفواتير'],
        'invoice-templates.index'           => ['title' => 'قوالب الفواتير', 'group' => 'الفواتير'],
        'sales.statistics'                  => ['title' => 'إحصائيات المبيعات', 'group' => 'الفواتير'],
        'purchases.statistics'              => ['title' => 'إحصائيات المشتريات', 'group' => 'الفواتير'],
        'inventory.statistics'              => ['title' => 'إحصائيات المخزون', 'group' => 'الفواتير'],

        // ── التقارير ──────────────────────────────────────────────
        'reports.index'                     => ['title' => 'التقارير العامة', 'group' => 'التقارير'],
        'reports.inactive-items'            => ['title' => 'تقرير الأصناف غير النشطة', 'group' => 'التقارير'],
        'item-sales'                        => ['title' => 'تقرير مبيعات الأصناف', 'group' => 'التقارير'],
        'item-purchase'                     => ['title' => 'تقرير مشتريات الأصناف', 'group' => 'التقارير'],
        'billing.invoice-report'            => ['title' => 'تقرير فواتير المشتريات', 'group' => 'التقارير'],
        'sales.invoice-report'              => ['title' => 'تقرير فواتير المبيعات', 'group' => 'التقارير'],
        'sales-orders-report'               => ['title' => 'تقرير أوامر البيع', 'group' => 'التقارير'],
        'purchase-quotations-reports'       => ['title' => 'تقرير عروض أسعار المشتريات', 'group' => 'التقارير'],
        'supplier-rfqs-report'              => ['title' => 'تقرير طلبات عروض الموردين', 'group' => 'التقارير'],
        'manufacturing.invoice.report'      => ['title' => 'تقرير فواتير التصنيع', 'group' => 'التقارير'],
        'reports.customer-quotations-comparison' => ['title' => 'مقارنة عروض أسعار العملاء', 'group' => 'التقارير'],

        // ── الأقساط ───────────────────────────────────────────────
        'installments.plans.index'          => ['title' => 'خطط الأقساط', 'group' => 'الأقساط'],
        'installments.plans.create'         => ['title' => 'إضافة خطة أقساط', 'group' => 'الأقساط'],
        'installments.payments.overdue'     => ['title' => 'الأقساط المتأخرة', 'group' => 'الأقساط'],

        // ── الموارد البشرية ───────────────────────────────────────
        'employees.index'                   => ['title' => 'قائمة الموظفين', 'group' => 'الموارد البشرية'],
        'departments.index'                 => ['title' => 'الأقسام', 'group' => 'الموارد البشرية'],
        'jobs.index'                        => ['title' => 'الوظائف', 'group' => 'الموارد البشرية'],
        'shifts.index'                      => ['title' => 'الورديات', 'group' => 'الموارد البشرية'],
        'attendances.index'                 => ['title' => 'الحضور والانصراف', 'group' => 'الموارد البشرية'],
        'kpis.index'                        => ['title' => 'مؤشرات الأداء', 'group' => 'الموارد البشرية'],
        'kpis.employeeEvaluation'           => ['title' => 'تقييم الموظفين', 'group' => 'الموارد البشرية'],
        'covenants.index'                   => ['title' => 'العهد', 'group' => 'الموارد البشرية'],
        'errands.index'                     => ['title' => 'المهام الخارجية', 'group' => 'الموارد البشرية'],
        'work-permissions.index'            => ['title' => 'إذونات العمل', 'group' => 'الموارد البشرية'],
        'hr.leaves.balances.index'          => ['title' => 'أرصدة الإجازات', 'group' => 'الموارد البشرية'],
        'hr.leaves.requests.index'          => ['title' => 'طلبات الإجازات', 'group' => 'الموارد البشرية'],
        'hr.leaves.types.manage'            => ['title' => 'أنواع الإجازات', 'group' => 'الموارد البشرية'],
        'hr.settings.index'                 => ['title' => 'إعدادات الموارد البشرية', 'group' => 'الموارد البشرية'],
        'countries.index'                   => ['title' => 'الدول', 'group' => 'الموارد البشرية'],
        'states.index'                      => ['title' => 'المحافظات', 'group' => 'الموارد البشرية'],
        'cities.index'                      => ['title' => 'المدن', 'group' => 'الموارد البشرية'],

        // ── التوظيف ───────────────────────────────────────────────
        'recruitment.dashboard'             => ['title' => 'لوحة التوظيف', 'group' => 'التوظيف'],
        'recruitment.cvs.index'             => ['title' => 'السير الذاتية', 'group' => 'التوظيف'],
        'recruitment.contracts.index'       => ['title' => 'العقود', 'group' => 'التوظيف'],
        'recruitment.job-postings.index'    => ['title' => 'الوظائف الشاغرة', 'group' => 'التوظيف'],
        'recruitment.interviews.index'      => ['title' => 'المقابلات', 'group' => 'التوظيف'],
        'recruitment.terminations.index'    => ['title' => 'إنهاء الخدمة', 'group' => 'التوظيف'],
        'recruitment.onboardings.index'     => ['title' => 'الاستقبال الوظيفي', 'group' => 'التوظيف'],
        'recruitment.contract-types.index'  => ['title' => 'أنواع العقود', 'group' => 'التوظيف'],

        // ── CRM ───────────────────────────────────────────────────
        'clients.index'                     => ['title' => 'قائمة العملاء', 'group' => 'CRM'],
        'leads.index'                       => ['title' => 'العملاء المحتملون', 'group' => 'CRM'],
        'leads.board'                       => ['title' => 'لوحة العملاء المحتملين', 'group' => 'CRM'],
        'tickets.index'                     => ['title' => 'تذاكر الدعم', 'group' => 'CRM'],
        'tasks.index'                       => ['title' => 'المهام', 'group' => 'CRM'],
        'tasks.kanban'                      => ['title' => 'لوحة المهام', 'group' => 'CRM'],
        'tasks.statistics'                  => ['title' => 'إحصائيات المهام', 'group' => 'CRM'],
        'activities.index'                  => ['title' => 'الأنشطة', 'group' => 'CRM'],
        'campaigns.index'                   => ['title' => 'الحملات التسويقية', 'group' => 'CRM'],
        'returns.index'                     => ['title' => 'المرتجعات', 'group' => 'CRM'],
        'statistics.index'                  => ['title' => 'إحصائيات CRM', 'group' => 'CRM'],
        'contacts.index'                    => ['title' => 'جهات الاتصال', 'group' => 'CRM'],
        'client.categories.index'           => ['title' => 'تصنيفات العملاء', 'group' => 'CRM'],
        'client-types.index'                => ['title' => 'أنواع العملاء', 'group' => 'CRM'],

        // ── الشيكات ───────────────────────────────────────────────
        'checks.index'                      => ['title' => 'الشيكات', 'group' => 'الشيكات'],
        'checks.incoming'                   => ['title' => 'أوراق القبض', 'group' => 'الشيكات'],
        'checks.incoming.create'            => ['title' => 'إضافة ورقة قبض', 'group' => 'الشيكات'],
        'checks.outgoing'                   => ['title' => 'أوراق الدفع', 'group' => 'الشيكات'],
        'checks.outgoing.create'            => ['title' => 'إضافة ورقة دفع', 'group' => 'الشيكات'],
        'checks.dashboard'                  => ['title' => 'لوحة الشيكات', 'group' => 'الشيكات'],
        'checks.management'                 => ['title' => 'إدارة الشيكات', 'group' => 'الشيكات'],

        // ── التصنيع ───────────────────────────────────────────────
        'manufacturing.index'               => ['title' => 'أوامر التصنيع', 'group' => 'التصنيع'],
        'manufacturing.statistics'          => ['title' => 'إحصائيات التصنيع', 'group' => 'التصنيع'],
        'manufacturing.templates.index'     => ['title' => 'قوالب التصنيع', 'group' => 'التصنيع'],
        'manufacturing.stages.index'        => ['title' => 'مراحل التصنيع', 'group' => 'التصنيع'],
        'manufacturing.orders.index'        => ['title' => 'طلبات التصنيع', 'group' => 'التصنيع'],
        'manufacturing.stage-invoices-report' => ['title' => 'تقرير فواتير مراحل التصنيع', 'group' => 'التصنيع'],

        // ── الصيانة ───────────────────────────────────────────────
        'maintenances.index'                => ['title' => 'طلبات الصيانة', 'group' => 'الصيانة'],
        'maintenance.dashboard.index'       => ['title' => 'لوحة الصيانة', 'group' => 'الصيانة'],
        'service.types.index'               => ['title' => 'أنواع الخدمات', 'group' => 'الصيانة'],
        'periodic.maintenances.index'       => ['title' => 'الصيانة الدورية', 'group' => 'الصيانة'],
        'periodic.maintenances.create'      => ['title' => 'إضافة صيانة دورية', 'group' => 'الصيانة'],

        // ── الأسطول ───────────────────────────────────────────────
        'fleet.dashboard.index'             => ['title' => 'لوحة الأسطول', 'group' => 'الأسطول'],
        'fleet.vehicles.index'              => ['title' => 'المركبات', 'group' => 'الأسطول'],
        'fleet.vehicle-types.index'         => ['title' => 'أنواع المركبات', 'group' => 'الأسطول'],
        'fleet.trips.index'                 => ['title' => 'الرحلات', 'group' => 'الأسطول'],
        'fleet.fuel-records.index'          => ['title' => 'سجلات الوقود', 'group' => 'الأسطول'],

        // ── الجودة ────────────────────────────────────────────────
        'quality.dashboard'                 => ['title' => 'لوحة الجودة', 'group' => 'الجودة'],
        'quality.inspections.index'         => ['title' => 'فحوصات الجودة', 'group' => 'الجودة'],
        'quality.standards.index'           => ['title' => 'معايير الجودة', 'group' => 'الجودة'],
        'quality.ncr.index'                 => ['title' => 'تقارير عدم المطابقة', 'group' => 'الجودة'],
        'quality.capa.index'                => ['title' => 'الإجراءات التصحيحية', 'group' => 'الجودة'],
        'quality.batches.index'             => ['title' => 'تتبع الدفعات', 'group' => 'الجودة'],
        'quality.certificates.index'        => ['title' => 'شهادات الجودة', 'group' => 'الجودة'],
        'quality.audits.index'              => ['title' => 'مراجعات الجودة', 'group' => 'الجودة'],
        'quality.suppliers.index'           => ['title' => 'تقييم الموردين', 'group' => 'الجودة'],

        // ── المشاريع ──────────────────────────────────────────────
        'projects.index'                    => ['title' => 'المشاريع', 'group' => 'المشاريع'],
        'projects.statistics'               => ['title' => 'إحصائيات المشاريع', 'group' => 'المشاريع'],
        'progress.dashboard'                => ['title' => 'لوحة تقدم المشاريع', 'group' => 'المشاريع'],
        'progress.projects.index'           => ['title' => 'مشاريع التقدم', 'group' => 'المشاريع'],
        'progress.issues.index'             => ['title' => 'المشكلات', 'group' => 'المشاريع'],
        'progress.issues.kanban'            => ['title' => 'لوحة المشكلات', 'group' => 'المشاريع'],
        'progress.project-templates.index'  => ['title' => 'قوالب المشاريع', 'group' => 'المشاريع'],

        // ── الاستفسارات ───────────────────────────────────────────
        'inquiries.index'                   => ['title' => 'الاستفسارات', 'group' => 'الاستفسارات'],
        'inquiries.create'                  => ['title' => 'إضافة استفسار', 'group' => 'الاستفسارات'],
        'inquiries.drafts'                  => ['title' => 'مسودات الاستفسارات', 'group' => 'الاستفسارات'],
        'inquiries.dashboard.statistics'    => ['title' => 'إحصائيات الاستفسارات', 'group' => 'الاستفسارات'],
        'inquiry.documents.index'           => ['title' => 'مستندات الاستفسارات', 'group' => 'الاستفسارات'],
        'inquiry.sources.index'             => ['title' => 'مصادر الاستفسارات', 'group' => 'الاستفسارات'],
        'work.types.index'                  => ['title' => 'أنواع الأعمال', 'group' => 'الاستفسارات'],

        // ── الإيجارات ─────────────────────────────────────────────
        'rentals.buildings.index'           => ['title' => 'المباني', 'group' => 'الإيجارات'],
        'rentals.units.index'               => ['title' => 'الوحدات', 'group' => 'الإيجارات'],
        'rentals.leases.index'              => ['title' => 'عقود الإيجار', 'group' => 'الإيجارات'],
        'rentals.statistics'                => ['title' => 'إحصائيات الإيجارات', 'group' => 'الإيجارات'],
        'rentals.reports'                   => ['title' => 'تقارير الإيجارات', 'group' => 'الإيجارات'],

        // ── الشحن ─────────────────────────────────────────────────
        'shipments.index'                   => ['title' => 'الشحنات', 'group' => 'الشحن'],
        'companies.index'                   => ['title' => 'شركات الشحن', 'group' => 'الشحن'],
        'drivers.index'                     => ['title' => 'السائقون', 'group' => 'الشحن'],
        'orders.index'                      => ['title' => 'طلبات الشحن', 'group' => 'الشحن'],
        'shipping.zones.index'              => ['title' => 'مناطق الشحن', 'group' => 'الشحن'],
        'shipping.dashboard.statistics'     => ['title' => 'إحصائيات الشحن', 'group' => 'الشحن'],

        // ── نقاط البيع ────────────────────────────────────────────
        'pos.index'                         => ['title' => 'نقاط البيع', 'group' => 'نقاط البيع'],
        'pos.create'                        => ['title' => 'فاتورة بيع جديدة', 'group' => 'نقاط البيع'],
        'pos.restaurant'                    => ['title' => 'واجهة المطعم', 'group' => 'نقاط البيع'],
        'pos.reports'                       => ['title' => 'تقارير نقاط البيع', 'group' => 'نقاط البيع'],
        'pos.settings'                      => ['title' => 'إعدادات نقاط البيع', 'group' => 'نقاط البيع'],
        'pos.setup.index'                   => ['title' => 'إعداد نقاط البيع', 'group' => 'نقاط البيع'],
        'pos.price-check'                   => ['title' => 'فحص الأسعار', 'group' => 'نقاط البيع'],

        // ── الخدمات ───────────────────────────────────────────────
        'services.services.index'           => ['title' => 'الخدمات', 'group' => 'الخدمات'],
        'services.bookings.index'           => ['title' => 'حجوزات الخدمات', 'group' => 'الخدمات'],
        'services.service-types.index'      => ['title' => 'أنواع الخدمات', 'group' => 'الخدمات'],
        'services.service-units.index'      => ['title' => 'وحدات الخدمات', 'group' => 'الخدمات'],
        'services.invoices.index'           => ['title' => 'فواتير الخدمات', 'group' => 'الخدمات'],

        // ── الإهلاك ───────────────────────────────────────────────
        'depreciation.index'                => ['title' => 'الإهلاك', 'group' => 'الأصول الثابتة'],
        'depreciation.schedule'             => ['title' => 'جدول الإهلاك', 'group' => 'الأصول الثابتة'],
        'depreciation.report'               => ['title' => 'تقرير الإهلاك', 'group' => 'الأصول الثابتة'],

        // ── الإعدادات ─────────────────────────────────────────────
        'mysettings.index'                  => ['title' => 'الإعدادات العامة', 'group' => 'الإعدادات'],
        'currencies.index'                  => ['title' => 'العملات', 'group' => 'الإعدادات'],
        'barcode.print.settings.edit'       => ['title' => 'إعدادات طباعة الباركود', 'group' => 'الإعدادات'],
        'export-settings'                   => ['title' => 'إعدادات التصدير', 'group' => 'الإعدادات'],
        'roles.index'                       => ['title' => 'الأدوار والصلاحيات', 'group' => 'الإعدادات'],
        'branches.index'                    => ['title' => 'الفروع', 'group' => 'الإعدادات'],

        // ── الوثائق والمستندات ────────────────────────────────────
        'documents.index'                   => ['title' => 'المستندات', 'group' => 'الوثائق'],
        'document-categories.index'         => ['title' => 'تصنيفات المستندات', 'group' => 'الوثائق'],
        'sops.index'                        => ['title' => 'إجراءات التشغيل', 'group' => 'الوثائق'],
        'sop-categories.index'              => ['title' => 'تصنيفات الإجراءات', 'group' => 'الوثائق'],

        // ── الموارد ───────────────────────────────────────────────
        'myresources.index'                 => ['title' => 'الموارد', 'group' => 'الموارد'],
        'myresources.dashboard'             => ['title' => 'لوحة الموارد', 'group' => 'الموارد'],
        'myresources.categories.index'      => ['title' => 'تصنيفات الموارد', 'group' => 'الموارد'],
        'myresources.types.index'           => ['title' => 'أنواع الموارد', 'group' => 'الموارد'],
        'myresources.assignments.index'     => ['title' => 'تعيينات الموارد', 'group' => 'الموارد'],

        // ── الإشعارات والسجلات ────────────────────────────────────
        'notifications.index'               => ['title' => 'الإشعارات', 'group' => 'النظام'],
        'activitylog.index'                 => ['title' => 'سجل النشاطات', 'group' => 'النظام'],
        'agent.index'                       => ['title' => 'المساعد الذكي', 'group' => 'النظام'],
        'agent.history'                     => ['title' => 'سجل المساعد الذكي', 'group' => 'النظام'],
        'helpcenter.index'                  => ['title' => 'مركز المساعدة', 'group' => 'النظام'],
        'helpcenter.admin.articles'         => ['title' => 'مقالات المساعدة', 'group' => 'النظام'],
        'helpcenter.admin.categories'       => ['title' => 'تصنيفات المساعدة', 'group' => 'النظام'],
        'zatca.index'                       => ['title' => 'زاتكا', 'group' => 'النظام'],
        'gamification.index'                => ['title' => 'نقاط التحفيز', 'group' => 'النظام'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Synonyms (Arabic & English)
    |--------------------------------------------------------------------------
    | Each group is a list of words that mean the same thing.
    | If the user types any word in a group, results matching ANY word
    | in that group will be returned.
    */
    'synonyms' => [
        // المشتريات
        ['مشتريات', 'شراء', 'موردين', 'بضاعة واردة', 'فاتورة شراء', 'purchase', 'purchases', 'buying'],

        // المبيعات
        ['مبيعات', 'بيع', 'عملاء', 'فاتورة بيع', 'sales', 'selling', 'sell'],

        // الفواتير
        ['فاتورة', 'فواتير', 'invoice', 'invoices', 'bill'],

        // الموظفين
        ['موظف', 'موظفين', 'عمال', 'كادر', 'employee', 'employees', 'staff', 'hr'],

        // الرواتب
        ['راتب', 'رواتب', 'مرتب', 'مرتبات', 'salary', 'payroll', 'salaries'],

        // الإجازات
        ['إجازة', 'إجازات', 'اجازة', 'اجازات', 'غياب', 'leave', 'leaves', 'vacation'],

        // الحضور
        ['حضور', 'انصراف', 'دوام', 'بصمة', 'attendance', 'timesheet'],

        // المخزون
        ['مخزون', 'مخزن', 'مستودع', 'بضاعة', 'inventory', 'stock', 'warehouse'],

        // الحسابات
        ['حساب', 'حسابات', 'دفتر', 'محاسبة', 'account', 'accounts', 'accounting', 'ledger'],

        // الشيكات
        ['شيك', 'شيكات', 'كمبيالة', 'ورقة قبض', 'ورقة دفع', 'check', 'checks', 'cheque'],

        // العملاء
        ['عميل', 'عملاء', 'زبون', 'زبائن', 'client', 'clients', 'customer', 'customers'],

        // الموردين
        ['مورد', 'موردين', 'مورّد', 'supplier', 'suppliers', 'vendor', 'vendors'],

        // المشاريع
        ['مشروع', 'مشاريع', 'project', 'projects'],

        // التقارير
        ['تقرير', 'تقارير', 'إحصاء', 'إحصائيات', 'report', 'reports', 'statistics', 'stats'],

        // الإعدادات
        ['إعداد', 'إعدادات', 'ضبط', 'setting', 'settings', 'configuration', 'config'],

        // الصيانة
        ['صيانة', 'إصلاح', 'عطل', 'maintenance', 'repair'],

        // التصنيع
        ['تصنيع', 'إنتاج', 'تصنيع', 'manufacturing', 'production', 'factory'],

        // الأصول الثابتة / الإهلاك
        ['إهلاك', 'أصول', 'أصل ثابت', 'depreciation', 'assets', 'fixed assets'],

        // الأقساط
        ['قسط', 'أقساط', 'تقسيط', 'installment', 'installments'],

        // الإيجارات
        ['إيجار', 'إيجارات', 'عقار', 'rental', 'rentals', 'lease'],

        // الشحن
        ['شحن', 'توصيل', 'شحنة', 'shipping', 'delivery', 'shipment'],

        // نقاط البيع
        ['كاشير', 'نقطة بيع', 'نقاط بيع', 'pos', 'cashier', 'point of sale'],

        // الجودة
        ['جودة', 'فحص', 'مراجعة', 'quality', 'inspection', 'audit'],

        // التوظيف
        ['توظيف', 'تعيين', 'مقابلة', 'سيرة ذاتية', 'recruitment', 'hiring', 'interview', 'cv'],

        // الأسطول
        ['أسطول', 'سيارة', 'مركبة', 'fleet', 'vehicle', 'car', 'truck'],

        // الخدمات
        ['خدمة', 'خدمات', 'حجز', 'service', 'services', 'booking'],

        // المستندات
        ['مستند', 'مستندات', 'وثيقة', 'وثائق', 'ملف', 'document', 'documents', 'file'],

        // الإجراءات
        ['إجراء', 'إجراءات', 'sop', 'sops', 'procedure'],

        // الفروع
        ['فرع', 'فروع', 'branch', 'branches'],

        // الصلاحيات
        ['صلاحية', 'صلاحيات', 'دور', 'أدوار', 'permission', 'permissions', 'role', 'roles'],

        // الإشعارات
        ['إشعار', 'إشعارات', 'تنبيه', 'notification', 'notifications'],

        // العملات
        ['عملة', 'عملات', 'سعر صرف', 'currency', 'currencies', 'exchange rate'],

        // الاستفسارات
        ['استفسار', 'استفسارات', 'inquiry', 'inquiries'],

        // الحملات
        ['حملة', 'حملات', 'تسويق', 'campaign', 'campaigns', 'marketing'],

        // المهام
        ['مهمة', 'مهام', 'task', 'tasks', 'todo'],

        // التذاكر
        ['تذكرة', 'تذاكر', 'دعم', 'ticket', 'tickets', 'support'],
    ],


    'exclude_name_prefixes' => [
        'ignition.',
        'debugbar.',
        'telescope.',
        'sanctum.',
        'universalsearch.',
    ],
    'exclude_uri_prefixes' => [
        '_ignition',
        '_debugbar',
        'api',
    ],

    /*
    |--------------------------------------------------------------------------
    | Limits
    |--------------------------------------------------------------------------
    */
    'min_query_length' => 2,
    'max_results' => 15,
];

