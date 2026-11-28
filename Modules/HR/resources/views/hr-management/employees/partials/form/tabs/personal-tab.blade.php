@php $p = $p ?? []; @endphp
<div>
    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient-primary text-white py-2">
                    <h6 class="card-title mb-0 font-hold fw-bold">
                        <i class="fas fa-user me-2"></i>{{ __('hr::hr.personal_information') }}
                    </h6>
                </div>
                <div class="card-body py-3">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold">{{ __('hr::hr.name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $p['name'] ?? '') }}" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold">{{ __('hr::hr.nickname') }}</label>
                            <input type="text" name="name_ar" class="form-control" value="{{ old('name_ar', $p['name_ar'] ?? '') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold">{{ __('hr::hr.english_name') }}</label>
                            <input type="text" name="name_en" class="form-control" value="{{ old('name_en', $p['name_en'] ?? '') }}" dir="ltr">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold">{{ __('hr::hr.employee_code') }}</label>
                            <input type="text" name="code" class="form-control" value="{{ old('code', $p['code'] ?? '') }}" dir="ltr">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold">{{ __('hr::hr.email') }}</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $p['email'] ?? '') }}" autocomplete="email">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold">{{ __('hr::hr.phone') }}</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $p['phone'] ?? '') }}" autocomplete="tel">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold">{{ __('hr::hr.national_id') }}</label>
                            <input type="text" name="nationalId" class="form-control" value="{{ old('nationalId', $p['nationalId'] ?? '') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold">{{ __('hr::hr.employee_gender') }}</label>
                            <select name="gender" class="form-select">
                                <option value="">{{ __('hr::hr.select_gender') }}</option>
                                <option value="male" @selected(old('gender', $p['gender'] ?? '') === 'male')>{{ __('hr::hr.male') }}</option>
                                <option value="female" @selected(old('gender', $p['gender'] ?? '') === 'female')>{{ __('hr::hr.female') }}</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold">{{ __('hr::hr.employee_date_of_birth') }}</label>
                            <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $p['date_of_birth'] ?? '') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold">{{ __('hr::hr.marital_status') }}</label>
                            <select name="marital_status" class="form-select">
                                <option value="">{{ __('hr::hr.select_marital_status') }}</option>
                                @foreach (['single', 'married', 'divorced', 'widowed'] as $ms)
                                    <option value="{{ $ms }}" @selected(old('marital_status', $p['marital_status'] ?? '') === $ms)>{{ __('hr::hr.'.$ms) }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold">{{ __('hr::hr.education_level') }}</label>
                            <select name="education" class="form-select">
                                <option value="">{{ __('hr::hr.select_education_level') }}</option>
                                @foreach (['diploma', 'bachelor', 'master', 'doctorate'] as $ed)
                                    <option value="{{ $ed }}" @selected(old('education', $p['education'] ?? '') === $ed)>{{ __('hr::hr.'.$ed) }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">{{ __('hr::hr.employee_information') }}</label>
                            <textarea name="information" class="form-control" rows="3">{{ old('information', $p['information'] ?? '') }}</textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient-success text-white py-2">
                    <h6 class="card-title mb-0 font-hold fw-bold">
                        <i class="fas fa-image me-2"></i>{{ __('hr::hr.employee_image') }}
                    </h6>
                </div>
                <div class="card-body text-center py-3">
                    <img id="employee-image-preview"
                         src="{{ $p['current_image_url'] ?? asset('assets/images/avatar-placeholder.svg') }}"
                         alt="{{ __('hr::hr.employee_image') }}"
                         class="rounded-circle border border-3 border-light shadow mb-3"
                         style="width: 120px; height: 120px; object-fit: cover;">
                    <input type="file" name="image" id="employee-image-input" class="form-control">
                    <div class="invalid-feedback"></div>
                    <small class="text-muted d-block mt-1">{{ __('hr::hr.file_upload_hint') }}</small>
                    <div class="mt-3 text-start">
                        <label class="form-label fw-bold">{{ __('hr::hr.employee_status') }}</label>
                        <select name="status" class="form-select">
                            <option value="">{{ __('hr::hr.select_status') }}</option>
                            @foreach (['resident', 'citizen', 'visitor', 'outside_company'] as $st)
                                <option value="{{ $st }}" @selected(old('status', $p['status'] ?? 'citizen') === $st)>{{ __('hr::hr.'.$st) }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
