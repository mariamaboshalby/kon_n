@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.work_permissions_management'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.work_permissions_management')],
        ],
    ])

    <div class="work-permissions-management" style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrWorkPermAlert" class="alert d-none" role="alert"></div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label font-hold fw-bold">{{ __('hr::hr.search_work_permissions') }}</label>
                        <input type="text" id="hrWorkPermSearch" class="form-control font-hold"
                               placeholder="{{ __('hr::hr.search_work_permissions') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold">{{ __('hr::hr.employee') }}</label>
                        <select id="hrWorkPermFilterEmployee" class="form-select font-hold js-searchable-filter">
                            <option value="">{{ __('hr::hr.all_employees') }}</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold">{{ __('hr::hr.status') }}</label>
                        <select id="hrWorkPermFilterStatus" class="form-select font-hold">
                            <option value="">{{ __('hr::hr.all') }}</option>
                            <option value="pending">{{ __('hr::hr.pending') }}</option>
                            <option value="approved">{{ __('hr::hr.approved') }}</option>
                            <option value="rejected">{{ __('hr::hr.rejected') }}</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label font-hold fw-bold mb-1">{{ __('hr::hr.date') }}</label>
                        <input type="date" id="hrWorkPermFilterStart" class="form-control font-hold">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label font-hold fw-bold mb-1">&mdash;</label>
                        <input type="date" id="hrWorkPermFilterEnd" class="form-control font-hold">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary flex-fill" id="hrWorkPermClearFilters">
                            <i class="las la-filter me-1"></i>{{ __('hr::hr.clear') }}
                        </button>
                        @can('create Work Permissions')
                            <button type="button" class="btn btn-main flex-fill" id="hrWorkPermCreateBtn">
                                <i class="las la-plus me-1"></i>{{ __('hr::hr.add_work_permission') }}
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-striped mb-0 text-center">
                    <thead class="table-light align-middle">
                        <tr>
                            <th class="font-hold fw-bold">#</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.employee') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.date') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.status') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.approved_by') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.created_at') }}</th>
                            @canany(['view Work Permissions', 'edit Work Permissions', 'delete Work Permissions', 'Leave Approvals', 'Leave Rejections'])
                                <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody id="hrWorkPermTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrWorkPermModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrWorkPermModalTitle">{{ __('hr::hr.add_work_permission') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrWorkPermForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.employee') }} <span class="text-danger">*</span></label>
                            <select name="employee_id" class="form-select font-hold js-searchable-filter" required>
                                <option value="">{{ __('hr::hr.select_employee') }}</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control font-hold" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3 d-none" id="hrWorkPermStatusWrap">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.status') }}</label>
                            <select name="status" class="form-select font-hold">
                                <option value="pending">{{ __('hr::hr.pending') }}</option>
                                <option value="approved">{{ __('hr::hr.approved') }}</option>
                                <option value="rejected">{{ __('hr::hr.rejected') }}</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-main" id="hrWorkPermSubmitBtn">{{ __('hr::hr.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrWorkPermViewModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold">{{ __('hr::hr.work_permission_details') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="hrWorkPermViewBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.close') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window._hrWorkPermissionsConfig = {
        routes: {
            data: @json(route('work-permissions.data')),
            store: @json(route('work-permissions.store')),
            update: @json(route('work-permissions.update', ['work_permission' => '__ID__'])),
            destroy: @json(route('work-permissions.destroy', ['work_permission' => '__ID__'])),
            show: @json(route('work-permissions.show', ['work_permission' => '__ID__'])),
            approve: @json(route('work-permissions.approve', ['work_permission' => '__ID__'])),
            reject: @json(route('work-permissions.reject', ['work_permission' => '__ID__']))
        },
        canView: @json(auth()->user()->can('view Work Permissions')),
        canEdit: @json(auth()->user()->can('edit Work Permissions')),
        canDelete: @json(auth()->user()->can('delete Work Permissions')),
        canApprove: @json(auth()->user()->can('Leave Approvals')),
        canReject: @json(auth()->user()->can('Leave Rejections')),
        csrfToken: @json(csrf_token()),
        trans: {
            add: @json(__('hr::hr.add_work_permission')),
            editTitle: @json(__('hr::hr.edit_work_permission')),
            save: @json(__('hr::hr.save')),
            update: @json(__('hr::hr.update')),
            empty: @json(__('hr::hr.no_work_permissions_found')),
            confirmDelete: @json(__('hr::hr.confirm_delete_work_permission_message')),
            confirmApprove: @json(__('hr::hr.confirm_approve_work_permission')),
            confirmReject: @json(__('hr::hr.confirm_reject_work_permission')),
            pending: @json(__('hr::hr.pending')),
            approved: @json(__('hr::hr.approved')),
            rejected: @json(__('hr::hr.rejected')),
            loadError: @json(__('hr::hr.error_occurred')),
            saveError: @json(__('hr::hr.error_occurred')),
            view: @json(__('hr::hr.view')),
            edit: @json(__('hr::hr.edit')),
            delete: @json(__('hr::hr.delete')),
            approve: @json(__('hr::hr.approve')),
            reject: @json(__('hr::hr.reject')),
            unknown: @json(__('hr::hr.unknown')),
            employee: @json(__('hr::hr.employee')),
            date: @json(__('hr::hr.date')),
            status: @json(__('hr::hr.status')),
            createdBy: @json(__('hr::hr.created_by')),
            approvedBy: @json(__('hr::hr.approved_by')),
            approvedAt: @json(__('hr::hr.approved_at')),
            createdAt: @json(__('hr::hr.created_at'))
        }
    };
</script>
<script src="{{ asset('js/hr-work-permissions.js') }}?v={{ filemtime(public_path('js/hr-work-permissions.js')) }}"></script>
@endpush
