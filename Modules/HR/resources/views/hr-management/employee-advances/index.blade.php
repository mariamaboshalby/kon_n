@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.employee_advances'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.employee_advances')],
        ],
    ])

    <div style="font-family: var(--masar-font-family); direction: rtl;" class="pb-5">
        <div id="hrAdvAlert" class="alert d-none" role="alert"></div>

        {{-- Top Action Bar --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-main" id="hrAdvFilterToggle">
                    <i class="las la-filter me-1"></i> {{ __('hr::hr.filters') }}
                </button>
                @can('create Employee Advances')
                    <button type="button" class="btn btn-main" id="hrAdvCreateBtn">
                        <i class="las la-plus"></i> {{ __('hr::hr.add') }}
                    </button>
                @endcan
            </div>
            <button type="button" class="btn btn-outline-info" id="hrAdvColsBtn" title="تخصيص الأعمدة">
                <i class="las la-sliders-h me-1"></i> تخصيص الأعمدة
            </button>
        </div>

        {{-- Filter Card (Collapsed by default) --}}
        <div id="hrAdvFilterCard" class="card mb-3" style="display: none;">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold" style="color: var(--main-color);">{{ __('hr::hr.date_from') }}</label>
                        <input type="date" id="hrAdvDateFrom" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold" style="color: var(--main-color);">{{ __('hr::hr.date_to') }}</label>
                        <input type="date" id="hrAdvDateTo" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold" style="color: var(--main-color);">{{ __('hr::hr.department') }}</label>
                        <select id="hrAdvSearchDepartment" class="form-select form-select-sm">
                            <option value="">{{ __('hr::hr.all_departments') }}</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label font-hold fw-bold" style="color: var(--main-color);">{{ __('hr::hr.employee') }}</label>
                        <select id="hrAdvSearchEmployee" class="form-select form-select-sm js-searchable-filter">
                            <option value="">{{ __('hr::hr.all_employees') }}</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold" style="color: var(--main-color);">{{ __('hr::hr.status') }}</label>
                        <select id="hrAdvSearchStatus" class="form-select form-select-sm">
                            <option value="">{{ __('hr::hr.all_statuses') }}</option>
                            <option value="pending">{{ __('hr::hr.pending') }}</option>
                            <option value="approved">{{ __('hr::hr.approved') }}</option>
                            <option value="rejected">{{ __('hr::hr.rejected') }}</option>
                        </select>
                    </div>

                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="hrAdvClear">
                            <i class="las la-redo"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body table-responsive">
                <table id="hrAdvTable" class="table table-striped text-center mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th data-col="id" style="width: 50px;">#</th>
                            <th data-col="employee">{{ __('hr::hr.employee') }}</th>
                            <th data-col="amount">{{ __('hr::hr.amount') }}</th>
                            <th data-col="date">{{ __('hr::hr.date') }}</th>
                            <th data-col="reason">{{ __('hr::hr.reason') }}</th>
                            <th data-col="status">{{ __('hr::hr.status') }}</th>
                            <th data-col="notes">{{ __('hr::hr.notes') }}</th>
                            @canany(['edit Employee Advances', 'delete Employee Advances'])
                                <th>{{ __('hr::hr.actions') }}</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody id="hrAdvTableBody">
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
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
                    <div class="col-6 col-md-6 text-end pe-4">
                        <span class="text-white-50 me-1">{{ __('hr::hr.total_records') }}:</span>
                        <strong id="hrAdvSummaryCount" class="text-warning">0</strong>
                    </div>
                    <div class="col-6 col-md-6 text-start ps-4">
                        <span class="text-white-50 me-1">{{ __('hr::hr.total_advances') }}:</span>
                        <strong id="hrAdvSummaryTotal" class="text-success fs-6">0.00</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal إضافة / تعديل السلف --}}
    <div class="modal fade" id="hrAdvModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrAdvModalTitle">{{ __('hr::hr.add') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrAdvForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('hr::hr.employee') }} *</label>
                            <select name="employee_id" class="form-select js-searchable-filter" required>
                                <option value="">{{ __('hr::hr.select_employee') }}</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('hr::hr.amount') }} *</label>
                            <input type="text" name="amount" class="form-control" inputmode="decimal" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('hr::hr.date') }} *</label>
                            <input type="date" name="date" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('hr::hr.reason') }} *</label>
                            <input type="text" name="reason" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('hr::hr.notes') }}</label>
                            <input type="text" name="notes" class="form-control">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-main" id="hrAdvSubmitBtn">{{ __('hr::hr.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal تخصيص الأعمدة --}}
    <div class="modal fade" id="hrAdvColsModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light py-2">
                    <h5 class="modal-title font-hold fw-bold">
                        <i class="las la-sliders-h me-1 text-primary"></i> تخصيص إظهار الأعمدة
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <p class="text-muted small mb-3 font-hold">اختر الأعمدة التي ترغب في إظهارها أو إخفائها من جدول السلف:</p>
                    <div class="row g-3" id="hrAdvColsList"></div>
                </div>
                <div class="modal-footer d-flex justify-content-between py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary font-hold" id="hrAdvResetColsBtn">إعادة الافتراضي</button>
                    <button type="button" class="btn btn-sm btn-main font-hold" data-bs-dismiss="modal">تطبيق وإغلاق</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window._hrAdvancesConfig = {
        routes: {
            data: @json(route('hr.employee-advances.data')),
            store: @json(route('hr.employee-advances.store')),
            update: @json(route('hr.employee-advances.update', ['advance' => '__ID__'])),
            destroy: @json(route('hr.employee-advances.destroy', ['advance' => '__ID__'])),
            approve: @json(route('hr.employee-advances.approve', ['advance' => '__ID__'])),
            reject: @json(route('hr.employee-advances.reject', ['advance' => '__ID__']))
        },
        canEdit: @json(auth()->user()->can('edit Employee Advances')),
        canDelete: @json(auth()->user()->can('delete Employee Advances')),
        csrfToken: @json(csrf_token()),
        trans: {
            add: @json(__('hr::hr.add')),
            editTitle: @json(__('hr::hr.edit')),
            empty: @json(__('hr::hr.no_data')),
            confirmDelete: @json(__('hr::hr.confirm_delete')),
            confirmApprove: @json(__('hr::hr.confirm_approve')),
            confirmReject: @json(__('hr::hr.confirm_reject')),
            pending: @json(__('hr::hr.pending')),
            approved: @json(__('hr::hr.approved')),
            rejected: @json(__('hr::hr.rejected'))
        }
    };
</script>
<script src="{{ asset('js/hr-employee-advances.js') }}"></script>
<script>
    // Filter toggle
    $('#hrAdvFilterToggle').on('click', function () {
        var $card = $('#hrAdvFilterCard');
        $card.slideToggle(200);
    });
</script>
@endpush
