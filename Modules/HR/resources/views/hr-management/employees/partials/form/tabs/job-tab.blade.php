@php $p = $p ?? []; @endphp
<div>
    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient-warning text-white py-2">
                    <h6 class="card-title mb-0 font-hold fw-bold">
                        <i class="fas fa-briefcase me-2"></i>{{ __('hr::hr.job') }}
                    </h6>
                </div>
                <div class="card-body py-3">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold">{{ __('hr::hr.department') }}</label>
                            <select name="department_id" id="hrEmpDepartment" class="form-select">
                                <option value="">{{ __('اختر القسم') }}</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}"
                                            data-director-id="{{ $department->director_id ?? '' }}"
                                            @selected((string) old('department_id', $p['department_id'] ?? '') === (string) $department->id)>
                                        {{ $department->title }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold">{{ __('مدير القسم') }}</label>
                            <select name="department_director_id" id="hrEmpDeptDirector" class="form-select js-searchable-filter">
                                <option value="">{{ __('اختر مدير القسم') }}</option>
                                @foreach ($departmentDirectors as $departmentEmployee)
                                    <option value="{{ $departmentEmployee->id }}">{{ $departmentEmployee->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">{{ __('المدير المباشر') }}</label>
                            <select name="line_manager_id" id="hrEmpLineManager" class="form-select js-searchable-filter">
                                <option value="">{{ __('اختر المدير المباشر') }}</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">{{ __('hr::hr.job') }}</label>
                            <select name="job_id" class="form-select">
                                <option value="">{{ __('اختر الوظيفة') }}</option>
                                @foreach ($jobs as $job)
                                    <option value="{{ $job->id }}" @selected((string) old('job_id', $p['job_id'] ?? '') === (string) $job->id)>
                                        {{ $job->title }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">{{ __('المستوى الوظيفي') }}</label>
                            <select name="job_level" class="form-select">
                                <option value="">{{ __('اختر المستوى') }}</option>
                                @foreach (['مبتدئ', 'متوسط', 'محترف'] as $level)
                                    <option value="{{ $level }}" @selected(old('job_level', $p['job_level'] ?? '') === $level)>{{ $level }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient-secondary text-white py-2">
                    <h6 class="card-title mb-0 font-hold fw-bold">
                        <i class="fas fa-calendar-alt me-2"></i>{{ __('تواريخ التوظيف') }}
                    </h6>
                </div>
                <div class="card-body py-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">{{ __('تاريخ التوظيف') }}</label>
                            <input type="date" name="date_of_hire" class="form-control" value="{{ old('date_of_hire', $p['date_of_hire'] ?? '') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">{{ __('تاريخ الالتحاق') }}</label>
                            <input type="date" name="date_of_joining" class="form-control" value="{{ old('date_of_joining', $p['date_of_joining'] ?? '') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">{{ __('تاريخ الانتهاء') }}</label>
                            <input type="date" name="date_of_fire" id="hrEmpDateOfFire" class="form-control" value="{{ old('date_of_fire', $p['date_of_fire'] ?? '') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var field = document.getElementById('hrEmpDateOfFire');
    if (!field) {
        return;
    }

    var DAY_MS = 86400000;
    var notifiedValue = null;

    function daysRemaining(value) {
        var today = new Date();
        today.setHours(0, 0, 0, 0);
        var end = new Date(value + 'T00:00:00');
        return Math.round((end.getTime() - today.getTime()) / DAY_MS);
    }

    function notify(value) {
        if (!value || typeof Swal === 'undefined') {
            return;
        }
        var remaining = daysRemaining(value);
        var title, text, icon;

        if (remaining < 0) {
            icon = 'warning';
            title = 'تنبيه: تاريخ الانتهاء في الماضي';
            text = 'تاريخ الانتهاء المحدد (' + value + ') قد مضى من ' + Math.abs(remaining) + ' يومًا.';
        } else if (remaining === 0) {
            icon = 'warning';
            title = 'تنبيه: ينتهي اليوم';
            text = 'تاريخ انتهاء الموظف هو اليوم (' + value + ').';
        } else if (remaining <= 30) {
            icon = 'warning';
            title = 'تنبيه: يقترب تاريخ الانتهاء';
            text = 'يتبقى ' + remaining + ' يومًا فقط على تاريخ الانتهاء (' + value + ').';
        } else {
            icon = 'info';
            title = 'تم تحديد تاريخ الانتهاء';
            text = 'تاريخ انتهاء الموظف: ' + value + ' (يتبقى ' + remaining + ' يومًا).';
        }

        Swal.fire({ icon: icon, title: title, text: text, confirmButtonText: 'حسنًا' });
    }

    field.addEventListener('change', function () {
        if (field.value === notifiedValue) {
            return;
        }
        notifiedValue = field.value;
        notify(field.value);
    });
})();
</script>
@endpush
