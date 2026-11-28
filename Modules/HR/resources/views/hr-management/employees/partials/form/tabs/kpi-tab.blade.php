@php $p = $p ?? []; @endphp
<div>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-gradient-primary text-white py-2">
            <h6 class="card-title mb-0 font-hold fw-bold">
                <i class="fas fa-chart-line me-2"></i>{{ __('hr::hr.kpis') }}
            </h6>
        </div>
        <div class="card-body py-3">
            <div class="card border-primary mb-3">
                <div class="card-header bg-light py-2">
                    <h6 class="card-title mb-0 font-hold fw-bold text-primary">{{ __('إضافة معدل أداء جديد') }}</h6>
                </div>
                <div class="card-body py-3">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">{{ __('اختر معدل الأداء') }}</label>
                            <select id="hrEmpKpiSelect" class="form-select">
                                <option value="">{{ __('ابحث عن معدل الأداء...') }}</option>
                                @foreach ($kpis as $kpi)
                                    <option value="{{ $kpi->id }}" data-desc="{{ e($kpi->description) }}">{{ $kpi->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" class="btn btn-main w-100" id="hrEmpAddKpiBtn">
                                <i class="fas fa-plus me-2"></i>{{ __('إضافة') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="hrEmpKpiList" class="row g-3 mb-3"></div>
            <div id="hrEmpKpiEmpty" class="alert alert-info text-center">
                {{ __('لم يتم إضافة أي معدلات أداء بعد.') }}
            </div>
            <div id="hrEmpKpiWeightAlert" class="alert alert-warning d-none"></div>
            <div class="progress mb-2" style="height: 22px;">
                <div id="hrEmpKpiProgress" class="progress-bar bg-warning" style="width: 0%">0%</div>
            </div>
            <div id="hrEmpKpiHiddenInputs"></div>
            <div class="invalid-feedback d-block" data-error-for="kpi_weights"></div>
        </div>
    </div>
</div>
