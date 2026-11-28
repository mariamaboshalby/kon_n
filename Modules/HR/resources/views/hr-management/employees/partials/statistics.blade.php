{{-- Employee statistics KPIs + Chart.js canvases --}}
<div class="hr-employees-stats mb-4" style="font-family: var(--masar-font-family);">
    <div class="row mb-3 g-3">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="mdi mdi-account-group text-primary" style="font-size: 2.5rem;"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">{{ __('hr.stats_total_employees') }}</h6>
                            <h3 class="mb-0">{{ $employeeStats['kpis']['total'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="mdi mdi-account-check text-success" style="font-size: 2.5rem;"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">{{ __('hr.stats_currently_employed') }}</h6>
                            <h3 class="mb-0">{{ $employeeStats['kpis']['currently_employed'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="mdi mdi-account-plus text-info" style="font-size: 2.5rem;"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">{{ __('hr.stats_hired_this_month') }}</h6>
                            <h3 class="mb-0">{{ $employeeStats['kpis']['hired_this_month'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="mdi mdi-account-remove text-danger" style="font-size: 2.5rem;"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">{{ __('hr.stats_left_this_month') }}</h6>
                            <h3 class="mb-0">{{ $employeeStats['kpis']['left_this_month'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="mb-0 fs-6">{{ __('hr.stats_by_status') }}</h5>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 260px;">
                        <canvas id="hrEmpStatusChart" aria-label="{{ __('hr.stats_by_status') }}"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="mb-0 fs-6">{{ __('hr.stats_by_department') }}</h5>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 260px;">
                        <canvas id="hrEmpDeptChart" aria-label="{{ __('hr.stats_by_department') }}"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="mb-0 fs-6">{{ __('hr.stats_hires_vs_exits') }}</h5>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 260px;">
                        <canvas id="hrEmpMonthlyChart" aria-label="{{ __('hr.stats_hires_vs_exits') }}"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
