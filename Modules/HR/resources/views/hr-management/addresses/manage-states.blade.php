@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.states'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.states')],
        ],
    ])

    <div style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrCrudAlert" class="alert d-none" role="alert"></div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                @can('create States')
                    <button type="button" class="btn btn-main font-hold fw-bold" id="hrStateCreateBtn">
                        <i class="fas fa-plus me-2"></i>{{ __('hr::hr.add_state') }}
                    </button>
                @else
                    <span></span>
                @endcan
                <input type="text" id="hrStateSearch" class="form-control w-auto" style="min-width: 200px;"
                       placeholder="{{ __('hr::hr.search_by_name') }}">
            </div>
            <div class="card-body table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="table-light text-center">
                        <tr>
                            <th class="font-hold fw-bold">#</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.name') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.country') }}</th>
                            @canany(['edit States', 'delete States'])
                                <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody id="hrStateTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrStateModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrStateModalTitle">{{ __('hr::hr.add_state') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrStateForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control font-hold fw-bold" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.country') }} <span class="text-danger">*</span></label>
                            <select name="country_id" class="form-control font-hold fw-bold" required>
                                <option value="">{{ __('hr::hr.select_country') }}</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}">{{ $country->title }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-main" id="hrStateSubmitBtn">{{ __('hr::hr.save') }}</button>
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
            data: @json(route('states.data')),
            store: @json(route('states.store')),
            update: @json(route('states.update', ['state' => '__ID__'])),
            destroy: @json(route('states.destroy', ['state' => '__ID__']))
        },
        fields: [{ name: 'title', type: 'text' }, { name: 'country_id', type: 'select' }],
        parent: { name: 'country_id', relation: 'country' },
        titleField: 'title',
        columnCount: {{ auth()->user()->canAny(['edit States', 'delete States']) ? 4 : 3 }},
        canEdit: @json(auth()->user()->can('edit States')),
        canDelete: @json(auth()->user()->can('delete States')),
        searchSelector: '#hrStateSearch',
        createBtnSelector: '#hrStateCreateBtn',
        tbodySelector: '#hrStateTableBody',
        modalSelector: '#hrStateModal',
        modalTitleSelector: '#hrStateModalTitle',
        formSelector: '#hrStateForm',
        submitBtnSelector: '#hrStateSubmitBtn',
        alertSelector: '#hrCrudAlert',
        csrfToken: @json(csrf_token()),
        trans: {
            add: @json(__('hr::hr.add_state')),
            editTitle: @json(__('hr::hr.edit_state')),
            save: @json(__('hr::hr.save')),
            update: @json(__('hr::hr.update')),
            edit: @json(__('hr::hr.edit')),
            delete: @json(__('hr::hr.delete')),
            empty: @json(__('hr::hr.no_states_found')),
            confirmDelete: @json(__('hr::hr.are_you_sure_you_want_to_delete_this_state')),
            saved: @json(__('hr::hr.state_created_successfully')),
            deleted: @json(__('hr::hr.state_deleted_successfully'))
        }
    };
</script>
<script src="{{ asset('js/hr-simple-crud.js') }}"></script>
@endpush
