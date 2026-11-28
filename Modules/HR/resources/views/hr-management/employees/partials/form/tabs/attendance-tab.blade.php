@php
    $p = $p ?? [];
    $isEdit = $isEdit ?? false;
@endphp
<div>
    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient-primary text-white py-2">
                    <h6 class="card-title mb-0 font-hold fw-bold">
                        <i class="fas fa-clock me-2"></i>{{ __('hr::hr.attendance') }}
                    </h6>
                </div>
                <div class="card-body py-3">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">{{ __('hr::hr.shift') }}</label>
                            <select name="shift_id" class="form-select">
                                <option value="">{{ __('اختر الشيفت') }}</option>
                                @foreach ($shifts as $shift)
                                    <option value="{{ $shift->id }}" @selected((string) old('shift_id', $p['shift_id'] ?? '') === (string) $shift->id)>
                                        {{ $shift->shift_type }} | {{ $shift->start_time }} - {{ $shift->end_time }} | {{ $shift->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">{{ __('hr::hr.finger_print_id') }}</label>
                            <input type="text" name="finger_print_id" class="form-control" value="{{ old('finger_print_id', $p['finger_print_id'] ?? '') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">{{ __('hr::hr.finger_print_name') }}</label>
                            <input type="text" name="finger_print_name" class="form-control" value="{{ old('finger_print_name', $p['finger_print_name'] ?? '') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="row g-2">
                        @foreach ([
                            'allowed_permission_days' => __('hr::hr.work_permissions'),
                            'allowed_late_days' => __('hr::hr.allowed_late_days'),
                            'allowed_absent_days' => __('hr::hr.allowed_absent_days'),
                            'days_off' => __('hr::hr.days_off'),
                            'allowed_errand_days' => __('hr::hr.allowed_errand_days'),
                        ] as $field => $label)
                            <div class="col-6 col-md-2">
                                <label class="form-label fw-bold">{{ $label }}</label>
                                <input type="text" name="{{ $field }}" class="form-control"
                                       value="{{ old($field, $p[$field] ?? 0) }}">
                                <div class="invalid-feedback"></div>
                            </div>
                        @endforeach
                        <div class="col-6 col-md-2">
                            <label class="form-label fw-bold">{{ __('hr::hr.is_errand_allowed') }}</label>
                            <div class="form-check mt-2">
                                <input type="hidden" name="is_errand_allowed" value="0">
                                <input type="checkbox" name="is_errand_allowed" value="1" class="form-check-input"
                                       @checked(old('is_errand_allowed', $p['is_errand_allowed'] ?? false))>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient-warning text-white py-2">
                    <h6 class="card-title mb-0 font-hold fw-bold">
                        <i class="fas fa-mobile-alt me-2"></i>{{ __('نظام الهاتف المحمول') }}
                    </h6>
                </div>
                <div class="card-body py-3">
                    <label class="form-label fw-bold">{{ __('باسورد الهاتف') }}</label>
                    <div class="input-group">
                        <input type="password" name="password" id="hrEmpPassword" class="form-control"
                               placeholder="{{ $isEdit ? __('اتركه فارغاً للحفاظ على الباسورد الحالي') : __('أدخل باسورد الهاتف') }}"
                               autocomplete="new-password">
                        <button class="btn btn-outline-secondary" type="button" id="hrEmpTogglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback"></div>
                </div>
            </div>
        </div>
    </div></div>

