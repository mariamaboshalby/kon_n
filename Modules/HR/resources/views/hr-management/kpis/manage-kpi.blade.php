@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.kpis_management'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.kpis_management')],
        ],
    ])

    <div class="kpis-management" style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrKpiAlert" class="alert d-none" role="alert"></div>

        {{-- Action Bar --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div class="d-flex flex-wrap gap-2">
                @can('create KPIs')
                    <button type="button" class="btn btn-main font-hold fw-bold" id="hrKpiCreateBtn">
                        <i class="fas fa-plus me-1"></i>{{ __('hr::hr.add_new_kpi') }}
                    </button>
                @endcan
                <button type="button" class="btn btn-main" id="hrKpiFilterToggle">
                    <i class="las la-filter me-1"></i>{{ __('hr::hr.filters') }}
                </button>
            </div>
            <input type="text" id="hrKpiSearch" class="form-control w-auto font-hold"
                   style="min-width: 220px;" placeholder="{{ __('hr::hr.search_kpis') }}">
        </div>

        {{-- Filter Card (Collapsed by default) --}}
        <div id="hrKpiFilterCard" class="card mb-3" style="display: none;">
            <div class="card-body py-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label font-hold fw-bold" style="color: var(--main-color);">
                            {{ __('hr::hr.date_from') }}
                        </label>
                        <input type="date" id="hrKpiDateFrom" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label font-hold fw-bold" style="color: var(--main-color);">
                            {{ __('hr::hr.date_to') }}
                        </label>
                        <input type="date" id="hrKpiDateTo" class="form-control form-control-sm">
                    </div>
                    <div class="col-auto d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="hrKpiClearBtn"
                                title="{{ __('hr::hr.reset_filter') }}">
                            <i class="las la-redo"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-striped text-center mb-0">
                    <thead class="table-light align-middle">
                        <tr>
                            <th class="font-hold fw-bold">#</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.kpi_name') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.description') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.created_at') }}</th>
                            @canany(['edit KPIs', 'delete KPIs'])
                                <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody id="hrKpiTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrKpiModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrKpiModalTitle">{{ __('hr::hr.add_new_kpi') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrKpiForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.kpi_name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control font-hold" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.description') }}</label>
                            <textarea name="description" class="form-control font-hold" rows="3"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-main" id="hrKpiSubmitBtn">{{ __('hr::hr.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window._hrKpisConfig = {
        routes: {
            data:    @json(route('kpis.data')),
            store:   @json(route('kpis.store')),
            update:  @json(route('kpis.update',  ['kpi' => '__ID__'])),
            destroy: @json(route('kpis.destroy', ['kpi' => '__ID__']))
        },
        canEdit:   @json(auth()->user()->can('edit KPIs')),
        canDelete: @json(auth()->user()->can('delete KPIs')),
        csrfToken: @json(csrf_token()),
        trans: {
            add:           @json(__('hr::hr.add_new_kpi')),
            editTitle:     @json(__('hr::hr.edit_kpi')),
            save:          @json(__('hr::hr.save')),
            update:        @json(__('hr::hr.update')),
            empty:         @json(__('hr::hr.no_kpis_found')),
            confirmDelete: @json(__('hr::hr.confirm_delete_kpi')),
            edit:          @json(__('hr::hr.edit')),
            delete:        @json(__('hr::hr.delete'))
        }
    };
</script>
<script src="{{ asset('js/hr-kpis.js') }}"></script>
<script>
    $('#hrKpiFilterToggle').on('click', function () {
        $('#hrKpiFilterCard').slideToggle(200);
    });
</script>
@endpush
