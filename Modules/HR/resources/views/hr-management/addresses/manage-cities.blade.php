@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.cities'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.cities')],
        ],
    ])

    <div style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrCrudAlert" class="alert d-none" role="alert"></div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                @can('create Cities')
                    <button type="button" class="btn btn-main font-hold fw-bold" id="hrCityCreateBtn">
                        <i class="fas fa-plus me-2"></i>{{ __('hr::hr.add_city') }}
                    </button>
                @else
                    <span></span>
                @endcan
                <input type="text" id="hrCitySearch" class="form-control w-auto" style="min-width: 200px;"
                       placeholder="{{ __('hr::hr.search_by_name') }}">
            </div>
            <div class="card-body table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="table-light text-center">
                        <tr>
                            <th class="font-hold fw-bold">#</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.name') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.state') }}</th>
                            @canany(['edit Cities', 'delete Cities'])
                                <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody id="hrCityTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrCityModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrCityModalTitle">{{ __('hr::hr.add_city') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrCityForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control font-hold fw-bold" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.state') }} <span class="text-danger">*</span></label>
                            <select name="state_id" class="form-control font-hold fw-bold" required>
                                <option value="">{{ __('hr::hr.select_state') }}</option>
                                @foreach ($states as $state)
                                    <option value="{{ $state->id }}">{{ $state->title }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-main" id="hrCitySubmitBtn">{{ __('hr::hr.save') }}</button>
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
            data: @json(route('cities.data')),
            store: @json(route('cities.store')),
            update: @json(route('cities.update', ['city' => '__ID__'])),
            destroy: @json(route('cities.destroy', ['city' => '__ID__']))
        },
        fields: [{ name: 'title', type: 'text' }, { name: 'state_id', type: 'select' }],
        parent: { name: 'state_id', relation: 'state' },
        titleField: 'title',
        columnCount: {{ auth()->user()->canAny(['edit Cities', 'delete Cities']) ? 4 : 3 }},
        canEdit: @json(auth()->user()->can('edit Cities')),
        canDelete: @json(auth()->user()->can('delete Cities')),
        searchSelector: '#hrCitySearch',
        createBtnSelector: '#hrCityCreateBtn',
        tbodySelector: '#hrCityTableBody',
        modalSelector: '#hrCityModal',
        modalTitleSelector: '#hrCityModalTitle',
        formSelector: '#hrCityForm',
        submitBtnSelector: '#hrCitySubmitBtn',
        alertSelector: '#hrCrudAlert',
        csrfToken: @json(csrf_token()),
        trans: {
            add: @json(__('hr::hr.add_city')),
            editTitle: @json(__('hr::hr.edit_city')),
            save: @json(__('hr::hr.save')),
            update: @json(__('hr::hr.update')),
            edit: @json(__('hr::hr.edit')),
            delete: @json(__('hr::hr.delete')),
            empty: @json(__('hr::hr.no_cities_found')),
            confirmDelete: @json(__('hr::hr.are_you_sure_you_want_to_delete_this_city')),
            saved: @json(__('hr::hr.city_created_successfully')),
            deleted: @json(__('hr::hr.city_deleted_successfully'))
        }
    };
</script>
<script src="{{ asset('js/hr-simple-crud.js') }}"></script>
@endpush
