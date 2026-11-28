@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.flexible_salary_processing'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.flexible_salary_processing')],
        ],
    ])

    <div style="font-family: var(--masar-font-family); direction: rtl;" class="pb-5">
        <div id="hrFlexAlert" class="alert d-none" role="alert"></div>

        {{-- Top Action Bar --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-main" id="hrFlexFilterToggle">
                    <i class="las la-filter me-1"></i> {{ __('hr::hr.filters') }}
                </button>
                @can('create Flexible Salary Processing')
                    <a href="{{ route('hr.flexible-salary.processing.create') }}" class="btn btn-main">
                        <i class="las la-plus"></i> {{ __('hr::hr.add') }}
                    </a>
                @endcan
            </div>
            <button type="button" class="btn btn-outline-info" id="hrFlexColsBtn" title="تخصيص الأعمدة">
                <i class="las la-sliders-h me-1"></i> تخصيص الأعمدة
            </button>
        </div>

        {{-- Filter Card (Collapsed by default per .cursorrules) --}}
        <div id="hrFlexFilterCard" class="card mb-3" style="display: none;">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold" style="color: var(--main-color);">{{ __('hr::hr.date_from') }}</label>
                        <input type="date" id="hrFilterStartDate" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold" style="color: var(--main-color);">{{ __('hr::hr.date_to') }}</label>
                        <input type="date" id="hrFilterEndDate" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold" style="color: var(--main-color);">{{ __('hr::hr.department') }}</label>
                        <select id="hrFilterDepartment" class="form-select form-select-sm">
                            <option value="">{{ __('hr::hr.all_departments') }}</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label font-hold fw-bold" style="color: var(--main-color);">{{ __('hr::hr.employee') }}</label>
                        <select id="hrFilterEmployee" class="form-select form-select-sm js-searchable-filter">
                            <option value="">{{ __('hr::hr.all_employees') }}</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold" style="color: var(--main-color);">{{ __('hr::hr.status') }}</label>
                        <select id="hrFilterStatus" class="form-select form-select-sm">
                            <option value="">{{ __('hr::hr.all_statuses') }}</option>
                            <option value="pending">{{ __('hr::hr.pending') }}</option>
                            <option value="approved">{{ __('hr::hr.approved') }}</option>
                            <option value="rejected">{{ __('hr::hr.rejected') }}</option>
                        </select>
                    </div>

                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="hrFlexClear">
                            <i class="las la-redo"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body table-responsive">
                <table id="hrFlexTable" class="table table-striped text-center mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th data-col="id" style="width: 50px;">#</th>
                            <th data-col="employee">{{ __('hr::hr.employee') }}</th>
                            <th data-col="department">{{ __('hr::hr.department') }}</th>
                            <th data-col="period">{{ __('hr::hr.period') }}</th>
                            <th data-col="fixed_salary">{{ __('hr::hr.fixed_salary') }}</th>
                            <th data-col="hours_worked">{{ __('hr::hr.hours_worked') }}</th>
                            <th data-col="hourly_wage">{{ __('hr::hr.hourly_wage') }}</th>
                            <th data-col="total_salary">{{ __('hr::hr.total_salary') }}</th>
                            <th data-col="status">{{ __('hr::hr.status') }}</th>
                            <th>{{ __('hr::hr.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody id="hrFlexTableBody">
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="las la-spinner la-spin me-2"></i> {{ __('hr::hr.loading') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Sticky Summary Footer Bar --}}
        <div class="position-fixed bottom-0 start-0 end-0 bg-dark text-white p-2 shadow-lg" style="z-index: 1040; border-top: 2px solid var(--main-color, #1a5632);">
            <div class="container-fluid">
                <div class="row text-center align-items-center g-2 font-hold" style="font-size: 0.9rem;">
                    <div class="col-6 col-md-3">
                        <span class="text-white-50 me-1">{{ __('hr::hr.total_records') }}:</span>
                        <strong id="hrSummaryCount" class="text-warning">0</strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-white-50 me-1">{{ __('hr::hr.total_hours') }}:</span>
                        <strong id="hrSummaryHours" class="text-info">0</strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-white-50 me-1">{{ __('hr::hr.total_fixed_salaries') }}:</span>
                        <strong id="hrSummaryFixed" class="text-light">0.00</strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-white-50 me-1">{{ __('hr::hr.total_flexible_amount') }}:</span>
                        <strong id="hrSummaryTotal" class="text-success fs-6">0.00</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal تخصيص الأعمدة --}}
    <div class="modal fade" id="hrFlexColsModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light py-2">
                    <h5 class="modal-title font-hold fw-bold">
                        <i class="las la-sliders-h me-1 text-primary"></i> تخصيص إظهار الأعمدة
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <p class="text-muted small mb-3 font-hold">اختر الأعمدة التي ترغب في إظهارها أو إخفائها من جدول معالجة الراتب المرن:</p>
                    <div class="row g-3" id="hrFlexColsList"></div>
                </div>
                <div class="modal-footer d-flex justify-content-between py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary font-hold" id="hrFlexResetColsBtn">إعادة الافتراضي</button>
                    <button type="button" class="btn btn-sm btn-main font-hold" data-bs-dismiss="modal">تطبيق وإغلاق</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window._hrFlexibleSalaryConfig = {
        mode: 'index',
        routes: {
            data: @json(route('hr.flexible-salary.processing.data')),
            edit: @json(route('hr.flexible-salary.processing.edit', ['processing' => '__ID__'])),
            approve: @json(route('hr.flexible-salary.processing.approve', ['processing' => '__ID__'])),
            reject: @json(route('hr.flexible-salary.processing.reject', ['processing' => '__ID__']))
        },
        canEdit: @json(auth()->user()->can('edit Flexible Salary Processing')),
        canApprove: @json(auth()->user()->can('edit Flexible Salary Approvals') || auth()->user()->can('edit Flexible Salary Processing')),
        csrfToken: @json(csrf_token()),
        trans: {
            empty: @json(__('hr::hr.no_data')),
            confirmApprove: @json(__('hr::hr.confirm_approve')),
            confirmReject: @json(__('hr::hr.confirm_reject')),
            edit: @json(__('hr::hr.edit')),
            pending: @json(__('hr::hr.pending')),
            approved: @json(__('hr::hr.approved')),
            rejected: @json(__('hr::hr.rejected'))
        }
    };
</script>
<script src="{{ asset('js/hr-flexible-salary.js') }}"></script>
<script>
    // Filter toggle — collapsed by default per .cursorrules
    $('#hrFlexFilterToggle').on('click', function () {
        $('#hrFlexFilterCard').slideToggle(200);
    });
</script>
@endpush
