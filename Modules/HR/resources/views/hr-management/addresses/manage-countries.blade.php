@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.countries'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.countries')],
        ],
    ])

    <div class="countries-management" style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrCrudAlert" class="alert d-none" role="alert"></div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                @can('create Countries')
                    <button type="button" class="btn btn-main font-hold fw-bold" id="hrCountryCreateBtn">
                        <i class="fas fa-plus me-2"></i>{{ __('hr::hr.add_country') }}
                    </button>
                @else
                    <span></span>
                @endcan
                <input type="text" id="hrCountrySearch" class="form-control w-auto" style="min-width: 200px;"
                       placeholder="{{ __('hr::hr.search_by_name') }}">
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="table-light text-center align-middle">
                            <tr>
                                <th class="font-hold fw-bold">#</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.name') }}</th>
                                @canany(['edit Countries', 'delete Countries'])
                                    <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                                @endcanany
                            </tr>
                        </thead>
                        <tbody id="hrCountryTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrCountryModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrCountryModalTitle">{{ __('hr::hr.add_country') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrCountryForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control font-hold fw-bold" required>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-main" id="hrCountrySubmitBtn">{{ __('hr::hr.save') }}</button>
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
            data: @json(route('countries.data')),
            store: @json(route('countries.store')),
            update: @json(route('countries.update', ['country' => '__ID__'])),
            destroy: @json(route('countries.destroy', ['country' => '__ID__']))
        },
        fields: [{ name: 'title', type: 'text' }],
        titleField: 'title',
        columnCount: {{ auth()->user()->canAny(['edit Countries', 'delete Countries']) ? 3 : 2 }},
        canEdit: @json(auth()->user()->can('edit Countries')),
        canDelete: @json(auth()->user()->can('delete Countries')),
        searchSelector: '#hrCountrySearch',
        createBtnSelector: '#hrCountryCreateBtn',
        tbodySelector: '#hrCountryTableBody',
        modalSelector: '#hrCountryModal',
        modalTitleSelector: '#hrCountryModalTitle',
        formSelector: '#hrCountryForm',
        submitBtnSelector: '#hrCountrySubmitBtn',
        alertSelector: '#hrCrudAlert',
        csrfToken: @json(csrf_token()),
        trans: {
            add: @json(__('hr::hr.add_country')),
            editTitle: @json(__('hr::hr.edit_country')),
            save: @json(__('hr::hr.save')),
            update: @json(__('hr::hr.update')),
            edit: @json(__('hr::hr.edit')),
            delete: @json(__('hr::hr.delete')),
            empty: @json(__('hr::hr.no_countries_found')),
            confirmDelete: @json(__('hr::hr.are_you_sure_you_want_to_delete_this_country')),
            saved: @json(__('hr::hr.country_created_successfully')),
            deleted: @json(__('hr::hr.country_deleted_successfully'))
        }
    };
</script>
<script src="{{ asset('js/hr-simple-crud.js') }}"></script>
@endpush
