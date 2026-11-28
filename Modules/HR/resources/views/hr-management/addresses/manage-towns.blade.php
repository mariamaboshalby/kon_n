@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.towns'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.towns')],
        ],
    ])

    <div style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrCrudAlert" class="alert d-none" role="alert"></div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                @can('create Towns')
                    <button type="button" class="btn btn-main font-hold fw-bold" id="hrTownCreateBtn">
                        <i class="fas fa-plus me-2"></i>{{ __('hr::hr.add_town') }}
                    </button>
                @else
                    <span></span>
                @endcan
                <input type="text" id="hrTownSearch" class="form-control w-auto" style="min-width: 200px;"
                       placeholder="{{ __('hr::hr.search_by_name') }}">
            </div>
            <div class="card-body table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="table-light text-center">
                        <tr>
                            <th class="font-hold fw-bold">#</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.name') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.city') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.distance') }}</th>
                            @canany(['edit Towns', 'delete Towns'])
                                <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody id="hrTownTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrTownModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrTownModalTitle">{{ __('hr::hr.add_town') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrTownForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control font-hold fw-bold" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.city') }} <span class="text-danger">*</span></label>
                            <select name="city_id" class="form-control font-hold fw-bold" required>
                                <option value="">{{ __('hr::hr.select_city') }}</option>
                                @foreach ($cities as $city)
                                    <option value="{{ $city->id }}">{{ $city->title }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.distance') }}</label>
                            <input type="text" name="distance" class="form-control font-hold fw-bold" inputmode="decimal">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-main" id="hrTownSubmitBtn">{{ __('hr::hr.save') }}</button>
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
            data: @json(route('towns.data')),
            store: @json(route('towns.store')),
            update: @json(route('towns.update', ['town' => '__ID__'])),
            destroy: @json(route('towns.destroy', ['town' => '__ID__']))
        },
        fields: [
            { name: 'title', type: 'text' },
            { name: 'city_id', type: 'select' },
            { name: 'distance', type: 'text' }
        ],
        parent: { name: 'city_id', relation: 'city' },
        extraColumns: [{ key: 'distance', format: 'number' }],
        titleField: 'title',
        columnCount: {{ auth()->user()->canAny(['edit Towns', 'delete Towns']) ? 5 : 4 }},
        canEdit: @json(auth()->user()->can('edit Towns')),
        canDelete: @json(auth()->user()->can('delete Towns')),
        searchSelector: '#hrTownSearch',
        createBtnSelector: '#hrTownCreateBtn',
        tbodySelector: '#hrTownTableBody',
        modalSelector: '#hrTownModal',
        modalTitleSelector: '#hrTownModalTitle',
        formSelector: '#hrTownForm',
        submitBtnSelector: '#hrTownSubmitBtn',
        alertSelector: '#hrCrudAlert',
        csrfToken: @json(csrf_token()),
        trans: {
            add: @json(__('hr::hr.add_town')),
            editTitle: @json(__('hr::hr.edit_town')),
            save: @json(__('hr::hr.save')),
            update: @json(__('hr::hr.update')),
            edit: @json(__('hr::hr.edit')),
            delete: @json(__('hr::hr.delete')),
            empty: @json(__('hr::hr.no_towns_found')),
            confirmDelete: @json(__('hr::hr.are_you_sure_you_want_to_delete_this_town')),
            saved: @json(__('hr::hr.town_created_successfully')),
            deleted: @json(__('hr::hr.town_deleted_successfully'))
        }
    };
</script>
<script src="{{ asset('js/hr-simple-crud.js') }}"></script>
@endpush
