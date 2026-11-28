@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.leave_types'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.leave_types')],
        ],
    ])

    <div style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrLeaveTypeAlert" class="alert d-none" role="alert"></div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0 font-hold fw-bold">{{ __('hr::hr.leave_types') }}</h2>
            @can('create Leave Types')
                <button type="button" class="btn btn-main font-hold fw-bold" id="hrLeaveTypeCreateBtn">
                    <i class="fas fa-plus me-2"></i>{{ __('hr::hr.add_leave_type') }}
                </button>
            @endcan
        </div>
        <div class="mb-3 col-md-6">
            <input type="text" id="hrLeaveTypeSearch" class="form-control font-hold"
                   placeholder="{{ __('hr::hr.search_by_name') }}">
        </div>
        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light text-center">
                        <tr>
                            <th class="font-hold fw-bold">{{ __('hr::hr.title') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.code') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.is_paid') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.requires_approval') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.max_per_request_days') }}</th>
                            @canany(['edit Leave Types', 'delete Leave Types'])
                                <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody id="hrLeaveTypeTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrLeaveTypeModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrLeaveTypeModalTitle">{{ __('hr::hr.add_leave_type') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrLeaveTypeForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.title') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control font-hold" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.code') }} <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control font-hold" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="form-check mb-2">
                            <input type="checkbox" name="is_paid" value="1" class="form-check-input" id="hrLeaveTypeIsPaid">
                            <label class="form-check-label font-hold" for="hrLeaveTypeIsPaid">{{ __('hr::hr.is_paid') }}</label>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" name="requires_approval" value="1" class="form-check-input" id="hrLeaveTypeRequiresApproval">
                            <label class="form-check-label font-hold" for="hrLeaveTypeRequiresApproval">{{ __('hr::hr.requires_approval') }}</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.max_per_request_days') }}</label>
                            <input type="text" name="max_per_request_days" class="form-control font-hold" inputmode="numeric" value="0" required>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-main" id="hrLeaveTypeSubmitBtn">{{ __('hr::hr.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window._hrLeaveTypesConfig = {
        routes: {
            data: @json(route('hr.leaves.types.data')),
            store: @json(route('hr.leaves.types.store')),
            update: @json(route('hr.leaves.types.update', ['leaveType' => '__ID__'])),
            destroy: @json(route('hr.leaves.types.destroy', ['leaveType' => '__ID__']))
        },
        canEdit: @json(auth()->user()->can('edit Leave Types')),
        canDelete: @json(auth()->user()->can('delete Leave Types')),
        csrfToken: @json(csrf_token()),
        trans: {
            add: @json(__('hr::hr.add_leave_type')),
            editTitle: @json(__('hr::hr.edit_leave_type')),
            save: @json(__('hr::hr.save')),
            update: @json(__('hr::hr.update')),
            empty: @json(__('hr::hr.no_leave_types_found')),
            confirmDelete: @json(__('hr::hr.confirm_delete_leave_type')),
            yes: @json(__('hr::hr.yes')),
            no: @json(__('hr::hr.no')),
            days: @json(__('hr::hr.days'))
        }
    };
</script>
<script src="{{ asset('js/hr-leave-types.js') }}"></script>
@endpush
