@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.employee_deductions_rewards'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.employee_deductions_rewards')],
        ],
    ])

    <div style="font-family: var(--masar-font-family); direction: rtl;" class="pb-5">
        <div id="hrDrAlert" class="alert d-none" role="alert"></div>

        {{-- Top Action Bar --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-main" id="hrDrFilterToggle">
                    <i class="las la-filter me-1"></i> {{ __('hr::hr.filters') }}
                </button>
                @can('create Employee Deductions')
                    <button type="button" class="btn btn-main" id="hrDrCreateBtn">
                        <i class="las la-plus"></i> {{ __('hr::hr.add') }}
                    </button>
                @endcan
            </div>
            <button type="button" class="btn btn-outline-info" id="hrDrColsBtn" title="تخصيص الأعمدة">
                <i class="las la-sliders-h me-1"></i> تخصيص الأعمدة
            </button>
        </div>

        {{-- Filter Card (Collapsed by default) --}}
        <div id="hrDrFilterCard" class="card mb-3" style="display: none;">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold" style="color: var(--main-color);">{{ __('hr::hr.date_from') }}</label>
                        <input type="date" id="hrDrDateFrom" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold" style="color: var(--main-color);">{{ __('hr::hr.date_to') }}</label>
                        <input type="date" id="hrDrDateTo" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold" style="color: var(--main-color);">{{ __('hr::hr.department') }}</label>
                        <select id="hrDrSearchDepartment" class="form-select form-select-sm">
                            <option value="">{{ __('hr::hr.all_departments') }}</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label font-hold fw-bold" style="color: var(--main-color);">{{ __('hr::hr.employee') }}</label>
                        <select id="hrDrSearchEmployee" class="form-select form-select-sm js-searchable-filter">
                            <option value="">{{ __('hr::hr.all_employees') }}</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold" style="color: var(--main-color);">{{ __('hr::hr.type') }}</label>
                        <select id="hrDrSearchType" class="form-select form-select-sm">
                            <option value="">{{ __('hr::hr.all') }}</option>
                            <option value="deduction">{{ __('hr::hr.deduction') }}</option>
                            <option value="reward">{{ __('hr::hr.reward') }}</option>
                        </select>
                    </div>

                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="hrDrClear">
                            <i class="las la-redo"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body table-responsive">
                <table id="hrDrTable" class="table table-striped text-center mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th data-col="id" style="width: 50px;">#</th>
                            <th data-col="employee">{{ __('hr::hr.employee') }}</th>
                            <th data-col="type">{{ __('hr::hr.type') }}</th>
                            <th data-col="amount">{{ __('hr::hr.amount') }}</th>
                            <th data-col="date">{{ __('hr::hr.date') }}</th>
                            <th data-col="reason">{{ __('hr::hr.reason') }}</th>
                            <th data-col="status">{{ __('hr::hr.status') }}</th>
                            <th data-col="notes">{{ __('hr::hr.notes') }}</th>
                            @canany(['edit Employee Deductions', 'delete Employee Deductions'])
                                <th>{{ __('hr::hr.actions') }}</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody id="hrDrTableBody">
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
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
                        <strong id="hrDrSummaryCount" class="text-warning">0</strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-white-50 me-1">{{ __('hr::hr.total_deductions') }}:</span>
                        <strong id="hrDrSummaryDeductions" class="text-danger">0.00</strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-white-50 me-1">{{ __('hr::hr.total_rewards') }}:</span>
                        <strong id="hrDrSummaryRewards" class="text-info">0.00</strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-white-50 me-1">{{ __('hr::hr.net_salary') }}:</span>
                        <strong id="hrDrSummaryNet" class="text-success fs-6">0.00</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal إضافة / تعديل الخصومات والمكافآت --}}
    <div class="modal fade" id="hrDrModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrDrModalTitle">{{ __('hr::hr.add') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrDrForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('hr::hr.employee') }} *</label>
                            <select name="employee_id" class="form-select js-searchable-filter" required>
                                <option value="">{{ __('hr::hr.select_employee') }}</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('hr::hr.type') }} *</label>
                            <select name="type" class="form-select">
                                <option value="deduction">{{ __('hr::hr.deduction') }}</option>
                                <option value="reward">{{ __('hr::hr.reward') }}</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('hr::hr.amount') }} *</label>
                            <input type="text" name="amount" class="form-control" inputmode="decimal" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('hr::hr.date') }} *</label>
                            <input type="date" name="date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('hr::hr.reason') }} *</label>
                            <input type="text" name="reason" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('hr::hr.notes') }}</label>
                            <input type="text" name="notes" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-main" id="hrDrSubmitBtn">{{ __('hr::hr.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal تخصيص الأعمدة --}}
    <div class="modal fade" id="hrDrColsModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light py-2">
                    <h5 class="modal-title font-hold fw-bold">
                        <i class="las la-sliders-h me-1 text-primary"></i> تخصيص إظهار الأعمدة
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <p class="text-muted small mb-3 font-hold">اختر الأعمدة التي ترغب في إظهارها أو إخفائها من جدول الخصومات والمكافآت:</p>
                    <div class="row g-3" id="hrDrColsList"></div>
                </div>
                <div class="modal-footer d-flex justify-content-between py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary font-hold" id="hrDrResetColsBtn">إعادة الافتراضي</button>
                    <button type="button" class="btn btn-sm btn-main font-hold" data-bs-dismiss="modal">تطبيق وإغلاق</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window._hrDeductionsConfig = {
        routes: {
            data: @json(route('hr.employee-deductions-rewards.data')),
            store: @json(route('hr.employee-deductions-rewards.store')),
            update: @json(route('hr.employee-deductions-rewards.update', ['item' => '__ID__'])),
            destroy: @json(route('hr.employee-deductions-rewards.destroy', ['item' => '__ID__'])),
            approve: @json(route('hr.employee-deductions-rewards.approve', ['item' => '__ID__']))
        },
        canEdit: @json(auth()->user()->can('edit Employee Deductions')),
        canDelete: @json(auth()->user()->can('delete Employee Deductions')),
        csrfToken: @json(csrf_token()),
        trans: {
            empty: @json(__('hr::hr.no_data')),
            confirmDelete: @json(__('hr::hr.confirm_delete')),
            confirmApprove: @json(__('hr::hr.confirm_approve')),
            deduction: @json(__('hr::hr.deduction')),
            reward: @json(__('hr::hr.reward')),
            pending: @json(__('hr::hr.pending')),
            approved: @json(__('hr::hr.approved'))
        }
    };
</script>
<script src="{{ asset('js/hr-employee-deductions.js') }}"></script>
<script>
    // Filter toggle
    $('#hrDrFilterToggle').on('click', function () {
        var $card = $('#hrDrFilterCard');
        $card.slideToggle(200);
    });
</script>
@endpush
