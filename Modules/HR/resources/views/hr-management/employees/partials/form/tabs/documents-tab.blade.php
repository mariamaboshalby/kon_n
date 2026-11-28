@php $p = $p ?? []; @endphp
<div>
    <div class="row g-3">

        {{-- الهوية الوطنية --}}
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-gradient-primary text-white py-2">
                    <h6 class="card-title mb-0 font-hold fw-bold">
                        <i class="fas fa-id-card me-2"></i>{{ __('hr::hr.national_id') }}
                    </h6>
                </div>
                <div class="card-body py-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">{{ __('hr::hr.document_number') }}</label>
                            <input type="text" name="national_id_number" class="form-control"
                                   value="{{ old('national_id_number', $p['national_id_number'] ?? '') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">{{ __('hr::hr.start_date') }}</label>
                            <input type="date" name="national_id_start" class="form-control"
                                   value="{{ old('national_id_start', $p['national_id_start'] ?? '') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">{{ __('hr::hr.expiry_date') }}</label>
                            <input type="date" name="national_id_expiry" class="form-control doc-expiry-input"
                                   data-label="{{ __('hr::hr.national_id') }}"
                                   value="{{ old('national_id_expiry', $p['national_id_expiry'] ?? '') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- الإقامة --}}
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-gradient-warning text-white py-2">
                    <h6 class="card-title mb-0 font-hold fw-bold">
                        <i class="fas fa-home me-2"></i>{{ __('hr::hr.residence') }}
                    </h6>
                </div>
                <div class="card-body py-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">{{ __('hr::hr.document_number') }}</label>
                            <input type="text" name="residence_number" class="form-control"
                                   value="{{ old('residence_number', $p['residence_number'] ?? '') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">{{ __('hr::hr.start_date') }}</label>
                            <input type="date" name="residence_start" class="form-control"
                                   value="{{ old('residence_start', $p['residence_start'] ?? '') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">{{ __('hr::hr.expiry_date') }}</label>
                            <input type="date" name="residence_expiry" class="form-control doc-expiry-input"
                                   data-label="{{ __('hr::hr.residence') }}"
                                   value="{{ old('residence_expiry', $p['residence_expiry'] ?? '') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- الرخصة --}}
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-gradient-success text-white py-2">
                    <h6 class="card-title mb-0 font-hold fw-bold">
                        <i class="fas fa-car me-2"></i>{{ __('hr::hr.license') }}
                    </h6>
                </div>
                <div class="card-body py-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">{{ __('hr::hr.document_number') }}</label>
                            <input type="text" name="license_number" class="form-control"
                                   value="{{ old('license_number', $p['license_number'] ?? '') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">{{ __('hr::hr.start_date') }}</label>
                            <input type="date" name="license_start" class="form-control"
                                   value="{{ old('license_start', $p['license_start'] ?? '') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">{{ __('hr::hr.expiry_date') }}</label>
                            <input type="date" name="license_expiry" class="form-control doc-expiry-input"
                                   data-label="{{ __('hr::hr.license') }}"
                                   value="{{ old('license_expiry', $p['license_expiry'] ?? '') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>


