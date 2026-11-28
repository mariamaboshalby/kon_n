@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @php
        $canBulkEdit = auth()->user()->can('edit Attendances');
        $canBulkDelete = auth()->user()->can('delete Attendances');
        $canBulk = $canBulkEdit || $canBulkDelete;
    @endphp

    @include('components.breadcrumb', [
        'title' => __('hr::hr.attendances'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.attendances')],
        ],
    ])

    <div style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrAttendanceAlert" class="alert d-none" role="alert"></div>
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label font-hold fw-bold">{{ __('hr::hr.employee') }}</label>
                        <select id="hrAttSearchEmployee" class="form-select js-searchable-filter"
                            data-placeholder="{{ __('hr::hr.search_employee_placeholder') }}">
                            <option value="">{{ __('hr::hr.select_employee') }}</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold">{{ __('اسم البصمة') }}</label>
                        <input type="text" id="hrAttSearchFingerprintName" class="form-control" placeholder="{{ __('اسم البصمة') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold">{{ __('hr::hr.date_from') }}</label>
                        <input type="date" id="hrAttDateFrom" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold">{{ __('hr::hr.date_to') }}</label>
                        <input type="date" id="hrAttDateTo" class="form-control">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" id="hrAttClear">{{ __('hr::hr.clear') }}</button>
                        <button type="button" class="btn btn-outline-info" id="hrAttColsBtn" title="تخصيص الأعمدة">
                            <i class="las la-sliders-h me-1"></i> تخصيص الأعمدة
                        </button>
                        @can('create Attendances')
                            <button type="button" class="btn btn-main" id="hrAttCreateBtn">
                                <i class="las la-plus"></i> {{ __('hr::hr.add') }}
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            @if ($canBulk)
                <div id="hrAttBulkBar" class="alert alert-light border d-none mb-0 mx-2 mt-2 py-1 px-2">
                    <div class="d-flex flex-wrap align-items-center gap-1">
                        <span class="fw-bold small" id="hrAttBulkCountLabel"></span>
                        <select id="hrAttBulkAction" class="form-select form-select-sm" style="max-width: 180px;">
                            <option value="">{{ __('hr::hr.bulk_select_action') }}</option>
                            @if ($canBulkEdit)
                                <option value="approve">{{ __('hr::hr.bulk_action_approve') }}</option>
                                <option value="reject">{{ __('hr::hr.bulk_action_reject') }}</option>
                            @endif
                            @if ($canBulkDelete)
                                <option value="delete">{{ __('hr::hr.bulk_action_delete') }}</option>
                            @endif
                        </select>
                        <button type="button" id="hrAttBulkApply" class="btn btn-sm btn-success" title="{{ __('hr::hr.bulk_apply') }}">
                            <i class="las la-check"></i>
                        </button>
                        <button type="button" id="hrAttBulkClear" class="btn btn-secondary btn-sm" title="{{ __('hr::hr.bulk_clear_selection') }}">
                            <i class="las la-times"></i>
                        </button>
                    </div>
                </div>
            @endif
            <div class="card-body table-responsive">
                <table class="table table-striped text-center mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            @if ($canBulk)
                                <th style="width: 40px;">
                                    <input type="checkbox" id="hrAttSelectAllPage" class="form-check-input" title="{{ __('hr::hr.bulk_select_page') }}">
                                </th>
                            @endif
                            <th data-col="id">رقم</th>
                            <th data-col="employee">{{ __('hr::hr.employee') }}</th>
                            <th data-col="employee_code">كود الموظف</th>
                            <th data-col="finger_print_id">رقم الموظف</th>
                            <th data-col="finger_print_name">اسم البصمة</th>
                            <th data-col="type">{{ __('hr::hr.type') }}</th>
                            <th data-col="date">{{ __('hr::hr.date') }}</th>
                            <th data-col="time">{{ __('hr::hr.time') }}</th>
                            <th data-col="location">{{ __('hr::hr.location') }}</th>
                            <th data-col="project_code">كود المشروع</th>
                            <th data-col="device_type">نوع الجهاز</th>
                            <th data-col="status">{{ __('hr::hr.status') }}</th>
                            <th data-col="notes">ملاحظات</th>
                            @canany(['edit Attendances', 'delete Attendances'])
                                <th>{{ __('hr::hr.actions') }}</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody id="hrAttTableBody"></tbody>
                </table>
                <div class="text-center py-2" id="hrAttLazyFooter">
                    <small class="text-muted d-none" id="hrAttLoadHint">{{ __('hr::hr.scroll_for_more') }}</small>
                    <small class="text-muted d-none" id="hrAttAllLoaded">{{ __('hr::hr.all_loaded') }}</small>
                </div>
                <div id="hrAttLoadSentinel" style="height: 1px;"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrAttModal" tabindex="-1" data-bs-backdrop="static" data-bs-focus="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrAttModalTitle">{{ __('hr::hr.add') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrAttForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('hr::hr.employee') }} *</label>
                            <select name="employee_id" id="hrAttEmployeeSelect" class="form-select js-searchable-filter" required
                                data-placeholder="{{ __('hr::hr.search_employee_placeholder') }}">
                                <option value="">{{ __('hr::hr.select_employee') }}</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('hr::hr.type') }} *</label>
                            <select name="type" class="form-select">
                                <option value="check_in">{{ __('hr::hr.check_in') }}</option>
                                <option value="check_out">{{ __('hr::hr.check_out') }}</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('hr::hr.date') }} *</label>
                                <input type="date" name="date" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('hr::hr.time') }} *</label>
                                <input type="time" name="time" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('hr::hr.status') }}</label>
                            <select name="status" class="form-select">
                                <option value="pending">{{ __('hr::hr.pending') }}</option>
                                <option value="approved">{{ __('hr::hr.approved') }}</option>
                                <option value="rejected">{{ __('hr::hr.rejected') }}</option>
                            </select>
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
                        <button type="submit" class="btn btn-main" id="hrAttSubmitBtn">{{ __('hr::hr.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal تخصيص الأعمدة -->
    <div class="modal fade" id="hrAttColsModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light py-2">
                    <h5 class="modal-title font-hold fw-bold">
                        <i class="las la-sliders-h me-1 text-primary"></i> تخصيص إظهار الأعمدة
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <p class="text-muted small mb-3 font-hold">اختر الأعمدة التي ترغب في إظهارها أو إخفائها من جدول الحضور:</p>
                    <div class="row g-3" id="hrAttColsList"></div>
                </div>
                <div class="modal-footer d-flex justify-content-between py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary font-hold" id="hrAttResetColsBtn">إعادة الافتراضي</button>
                    <button type="button" class="btn btn-sm btn-main font-hold" data-bs-dismiss="modal">تطبيق وإغلاق</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window._hrAttendanceConfig = {
        routes: {
            data: @json(route('hr.attendance.data')),
            store: @json(route('hr.attendance.store')),
            update: @json(route('hr.attendance.update', ['attendance' => '__ID__'])),
            destroy: @json(route('hr.attendance.destroy', ['attendance' => '__ID__'])),
            bulkActions: @json(route('hr.attendance.bulk-actions'))
        },
        perPage: 50,
        canEdit: @json($canBulkEdit),
        canDelete: @json($canBulkDelete),
        canBulk: @json($canBulk),
        csrfToken: @json(csrf_token()),
        trans: {
            add: @json(__('hr::hr.add')),
            editTitle: @json(__('hr::hr.edit')),
            save: @json(__('hr::hr.save')),
            update: @json(__('hr::hr.update')),
            empty: @json(__('hr::hr.no_data')),
            confirmDelete: @json(__('hr::hr.confirm_delete')),
            selectEmployee: @json(__('hr::hr.select_employee')),
            searchEmployeePlaceholder: @json(__('hr::hr.search_employee_placeholder')),
            checkIn: @json(__('hr::hr.check_in')),
            checkOut: @json(__('hr::hr.check_out')),
            pending: @json(__('hr::hr.pending')),
            approved: @json(__('hr::hr.approved')),
            rejected: @json(__('hr::hr.rejected')),
            loadError: @json(__('hr::hr.error_loading')),
            scrollForMore: @json(__('hr::hr.scroll_for_more')),
            allLoaded: @json(__('hr::hr.all_loaded')),
            bulkSelectedCount: @json(__('hr::hr.bulk_selected_count')),
            bulkSelectFirst: @json(__('hr::hr.bulk_select_first')),
            bulkChooseActionFirst: @json(__('hr::hr.bulk_choose_action_first')),
            confirmBulkApprove: @json(__('hr::hr.confirm_bulk_approve')),
            confirmBulkReject: @json(__('hr::hr.confirm_bulk_reject')),
            confirmBulkDelete: @json(__('hr::hr.confirm_bulk_delete'))
        }
    };
</script>
<script src="{{ asset('js/hr-attendance.js') }}?v={{ filemtime(public_path('js/hr-attendance.js')) }}"></script>
@endpush
