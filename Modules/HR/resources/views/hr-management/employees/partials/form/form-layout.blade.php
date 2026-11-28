@php
    $isEdit = $isEdit ?? false;
    $formPayload = $formPayload ?? null;
    $p = $formPayload ?? [];
@endphp

<div class="employee-form-container" style="font-family: var(--masar-font-family); direction: rtl;">
    <div id="hrEmployeeFormAlert" class="alert d-none" role="alert"></div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 font-hold fw-bold">
                        <i class="fas {{ $isEdit ? 'fa-user-edit' : 'fa-user-plus' }} me-2"></i>{{ $title }}
                    </h5>
                    <a href="{{ route('employees.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>{{ __('hr::hr.back_to_list') }}
                    </a>
                </div>

                <div class="card-body">
                    <div id="hrEmployeeValidationErrors" class="alert alert-danger d-none" role="alert">
                        <h6 class="alert-heading mb-2">{{ __('hr::hr.validation_errors') }}</h6>
                        <ul class="mb-0 ps-3" id="hrEmployeeValidationList"></ul>
                    </div>

                    <form id="hrEmployeeForm"
                          action="{{ $formAction }}"
                          method="POST"
                          enctype="multipart/form-data"
                          novalidate>
                        @csrf
                        @if ($isEdit)
                            @method('PUT')
                            <input type="hidden" name="isEdit" value="1">
                            <input type="hidden" name="employeeId" value="{{ $employee->id ?? '' }}">
                        @endif

                        @include('hr::hr-management.employees.partials.form.employee-form', [
                            'isEdit' => $isEdit,
                            'p' => $p,
                        ])
                    </form>
                </div>

                <div class="card-footer bg-light border-top form-actions">
                    <div class="d-flex justify-content-center gap-3 flex-column flex-sm-row">
                        <a href="{{ route('employees.index') }}" class="btn btn-secondary btn-lg">
                            <i class="fas fa-times me-2"></i>{{ __('hr::hr.cancel') }}
                        </a>
                        <button type="submit" form="hrEmployeeForm" class="btn btn-main btn-lg js-employee-submit">
                            <i class="fas fa-save me-2"></i>{{ $isEdit ? __('hr::hr.update') : __('hr::hr.save') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
    @include('hr::hr-management.employees.partials.style.style')
@endpush

@push('scripts')
<script src="{{ asset('js/form-submit-guard.js') }}?v={{ @filemtime(public_path('js/form-submit-guard.js')) }}"></script>
<script>
    window._hrEmployeeFormConfig = {
        isEdit: @json((bool) $isEdit),
        storeUrl: @json($formAction),
        method: @json($isEdit ? 'PUT' : 'POST'),
        csrfToken: @json(csrf_token()),
        formPayload: @json($formPayload),
        lookups: {
            states: @json($states),
            cities: @json($cities),
            towns: @json($towns),
            departments: @json($departments),
            employees: @json($employees),
            departmentDirectors: @json($departmentDirectors),
            kpis: @json($kpis),
            leaveTypes: @json($leaveTypes),
        },
        cascade: {
            states: @json(url('employees/cascade/states')),
            cities: @json(url('employees/cascade/cities')),
            towns: @json(url('employees/cascade/towns')),
        },
        currentYear: {{ (int) now()->year }},
        lang: {
            saving: @json(__('hr::hr.saving')),
            save: @json($isEdit ? __('hr::hr.update') : __('hr::hr.save')),
            error: @json(__('hr::hr.error_occurred')),
            selectState: @json(__('hr::hr.select_state') !== 'hr::hr.select_state' ? __('hr::hr.select_state') : __('اختر المحافظة')),
            selectCity: @json(__('اختر المدينة')),
            selectTown: @json(__('اختر المنطقة')),
            selectLineManager: @json(__('اختر المدير المباشر')),
            confirmRemoveKpi: @json(__('هل أنت متأكد من حذف هذا المعدل؟')),
            confirmRemoveLeave: @json(__('هل أنت متأكد من حذف رصيد الإجازة؟')),
            weightOk: @json(__('ممتاز! تم اكتمال النسبة بنجاح.')),
            weightHigh: @json('المجموع الحالي :total% أكبر من 100%.'),
            weightLow: @json('المجموع الحالي :total% أقل من 100%.'),
        }
    };
</script>
<script src="{{ asset('js/hr-employees-form.js') }}?v={{ @filemtime(public_path('js/hr-employees-form.js')) }}" defer></script>
<script>
(function () {
    var DAY_MS = 86400000;
    var shownAlert = false;

    function daysRemaining(v) {
        var t = new Date(); t.setHours(0,0,0,0);
        return Math.round((new Date(v + 'T00:00:00') - t) / DAY_MS);
    }

    function buildAlert(field) {
        if (!field.value) return null;
        var remaining = daysRemaining(field.value);
        var label = field.dataset.label || '';
        if (remaining < 0)   return { icon: 'error',   title: 'منتهي الصلاحية',       text: label + ' منتهي منذ ' + Math.abs(remaining) + ' يوم (' + field.value + ').' };
        if (remaining === 0) return { icon: 'warning',  title: 'ينتهي اليوم',           text: label + ' ينتهي اليوم (' + field.value + ').' };
        if (remaining <= 30) return { icon: 'warning',  title: 'تنبيه: يقترب الانتهاء', text: label + ' ينتهي خلال ' + remaining + ' يوم (' + field.value + ').' };
        return null;
    }

    function showQueuedAlerts(fields) {
        if (typeof Swal === 'undefined') return;
        var alerts = [];
        fields.forEach(function (f) { var a = buildAlert(f); if (a) alerts.push(a); });
        if (!alerts.length) return;
        // عرض التنبيهات بالتسلسل
        (function next(i) {
            if (i >= alerts.length) return;
            Swal.fire(Object.assign({}, alerts[i], { confirmButtonText: 'حسنًا' })).then(function () { next(i + 1); });
        })(0);
    }

    function attachDocExpiry() {
        document.querySelectorAll('.doc-expiry-input').forEach(function (field) {
            if (field._docExpiryBound) return;
            field._docExpiryBound = true;
            field.addEventListener('change', function () {
                var a = buildAlert(field);
                if (a && typeof Swal !== 'undefined') Swal.fire(Object.assign({}, a, { confirmButtonText: 'حسنًا' }));
            });
        });
    }

    function checkOnTabOpen() {
        if (shownAlert) return;
        var fields = Array.from(document.querySelectorAll('.doc-expiry-input'));
        var hasWarning = fields.some(function (f) { return f.value && daysRemaining(f.value) <= 30; });
        if (!hasWarning) return;
        shownAlert = true;
        showQueuedAlerts(fields);
    }

    document.addEventListener('DOMContentLoaded', attachDocExpiry);

    // عند فتح تاب الوثائق: ربط الـ events + فحص القيم الموجودة مسبقاً
    document.addEventListener('shown.bs.tab', function (e) {
        attachDocExpiry();
        if (e.target && e.target.getAttribute('data-hr-tab') === 'documents') {
            checkOnTabOpen();
        }
    });
})();
</script>
@endpush
