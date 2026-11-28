@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.instant_evaluation_items'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.instant_evaluation_items')],
        ],
    ])

    <div class="instant-evaluation-items" style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrIeItemAlert" class="alert d-none" role="alert"></div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <input type="text" id="hrIeItemSearch" class="form-control font-hold" placeholder="{{ __('hr::hr.search') }}">
                    </div>
                    <div class="col-md-3">
                        <select id="hrIeItemPolarityFilter" class="form-select font-hold">
                            <option value="">{{ __('hr::hr.instant_evaluation_polarity') }}</option>
                            <option value="positive">{{ __('hr::hr.instant_evaluation_positive') }}</option>
                            <option value="negative">{{ __('hr::hr.instant_evaluation_negative') }}</option>
                        </select>
                    </div>
                    <div class="col-md-5 text-md-end">
                        @can('create Instant Evaluation Items')
                            <button type="button" class="btn btn-main font-hold fw-bold" id="hrIeItemCreateBtn">
                                <i class="las la-plus me-1"></i>{{ __('hr::hr.add_instant_evaluation_item') }}
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-bordered text-center mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="font-hold fw-bold">#</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.name') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.instant_evaluation_polarity') }}</th>
                            <th class="font-hold fw-bold d-none d-md-table-cell">{{ __('hr::hr.description') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.instant_evaluation_item_active') }}</th>
                            @canany(['edit Instant Evaluation Items', 'delete Instant Evaluation Items'])
                                <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody id="hrIeItemTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrIeItemModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrIeItemModalTitle">{{ __('hr::hr.add_instant_evaluation_item') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrIeItemForm">
                    <input type="hidden" id="hrIeItemId" value="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="hrIeItemName" class="form-control font-hold" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.instant_evaluation_polarity') }} <span class="text-danger">*</span></label>
                            <select name="polarity" id="hrIeItemPolarity" class="form-select font-hold" required>
                                <option value="positive">{{ __('hr::hr.instant_evaluation_positive') }}</option>
                                <option value="negative">{{ __('hr::hr.instant_evaluation_negative') }}</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.description') }}</label>
                            <textarea name="description" id="hrIeItemDescription" class="form-control font-hold" rows="3"></textarea>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="hrIeItemActive" checked>
                            <label class="form-check-label font-hold" for="hrIeItemActive">{{ __('hr::hr.instant_evaluation_item_active') }}</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary font-hold" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-main font-hold fw-bold" id="hrIeItemSubmitBtn">{{ __('hr::hr.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window._hrInstantEvaluationItemsConfig = {
        urls: {
            data: @json(route('instant-evaluation-items.data')),
            store: @json(route('instant-evaluation-items.store')),
            update: @json(url('instant-evaluation-items')),
            destroy: @json(url('instant-evaluation-items')),
        },
        csrfToken: @json(csrf_token()),
        canEdit: @json(auth()->user()->can('edit Instant Evaluation Items')),
        canDelete: @json(auth()->user()->can('delete Instant Evaluation Items')),
        lang: {
            add: @json(__('hr::hr.add_instant_evaluation_item')),
            edit: @json(__('hr::hr.edit_instant_evaluation_item')),
            empty: @json(__('hr::hr.no_instant_evaluation_items_found')),
            confirmDelete: @json(__('hr::hr.confirm_delete_instant_evaluation_item')),
            positive: @json(__('hr::hr.instant_evaluation_positive')),
            negative: @json(__('hr::hr.instant_evaluation_negative')),
            yes: @json(__('hr::hr.yes')),
            no: @json(__('hr::hr.no')),
            delete: @json(__('hr::hr.delete')),
            editBtn: @json(__('hr::hr.edit')),
        }
    };
</script>
<script src="{{ asset('js/hr-instant-evaluation-items.js') }}?v={{ @filemtime(public_path('js/hr-instant-evaluation-items.js')) }}"></script>
@endpush
