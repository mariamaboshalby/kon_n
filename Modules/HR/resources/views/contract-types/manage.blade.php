@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('recruitment.contract_types'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('recruitment.recruitment_management')],
            ['label' => __('recruitment.contract_types')],
        ],
    ])

    <div class="contract-types-management" style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrCrudAlert" class="alert d-none" role="alert"></div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                @can('create Contract Types')
                    <button type="button" class="btn btn-main font-hold fw-bold" id="hrContractTypeCreateBtn">
                        <i class="fas fa-plus me-2"></i>{{ __('hr::hr.add_contract_type') }}
                    </button>
                @else
                    <span></span>
                @endcan
                <input type="text" id="hrContractTypeSearch" class="form-control w-auto" style="min-width: 200px;"
                       placeholder="{{ __('hr::hr.search_by_name') }}">
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped mb-0 text-center">
                        <thead class="table-light align-middle">
                            <tr>
                                <th class="font-hold fw-bold">#</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.title') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.description') }}</th>
                                @canany(['edit Contract Types', 'delete Contract Types'])
                                    <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                                @endcanany
                            </tr>
                        </thead>
                        <tbody id="hrContractTypeTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrContractTypeModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrContractTypeModalTitle">{{ __('hr::hr.add_contract_type') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrContractTypeForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.title') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control font-hold fw-bold" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.description') }}</label>
                            <textarea name="description" class="form-control font-hold fw-bold" rows="3"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-main" id="hrContractTypeSubmitBtn">{{ __('hr::hr.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window._hrSimpleCrudConfig = {
        routes: {
            data: @json(route('recruitment.contract-types.data')),
            store: @json(route('recruitment.contract-types.store')),
            update: @json(route('recruitment.contract-types.update', ['contract_type' => '__ID__'])),
            destroy: @json(route('recruitment.contract-types.destroy', ['contract_type' => '__ID__']))
        },
        fields: [
            { name: 'name', type: 'text' },
            { name: 'description', type: 'text' }
        ],
        titleField: 'name',
        columnCount: {{ auth()->user()->canAny(['edit Contract Types', 'delete Contract Types']) ? 4 : 3 }},
        canEdit: @json(auth()->user()->can('edit Contract Types')),
        canDelete: @json(auth()->user()->can('delete Contract Types')),
        extraColumns: [{ key: 'description' }],
        searchSelector: '#hrContractTypeSearch',
        createBtnSelector: '#hrContractTypeCreateBtn',
        tbodySelector: '#hrContractTypeTableBody',
        modalSelector: '#hrContractTypeModal',
        modalTitleSelector: '#hrContractTypeModalTitle',
        formSelector: '#hrContractTypeForm',
        submitBtnSelector: '#hrContractTypeSubmitBtn',
        alertSelector: '#hrCrudAlert',
        csrfToken: @json(csrf_token()),
        trans: {
            add: @json(__('hr::hr.add_contract_type')),
            editTitle: @json(__('hr::hr.edit_contract_type')),
            save: @json(__('hr::hr.save')),
            update: @json(__('hr::hr.update')),
            edit: @json(__('hr::hr.edit')),
            delete: @json(__('hr::hr.delete')),
            empty: @json(__('hr::hr.no_contract_types_found')),
            confirmDelete: @json(__('hr::hr.confirm_delete_contract_type')),
            saved: @json(__('hr::hr.contract_type_created_successfully')),
            deleted: @json(__('hr::hr.contract_type_deleted_successfully'))
        }
    };
</script>
<script src="{{ asset('js/hr-simple-crud.js') }}"></script>
@endpush
