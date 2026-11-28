@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('recruitment.terminations'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('recruitment.recruitment_management')],
            ['label' => __('recruitment.terminations')],
        ],
    ])

    <div class="hr-termination-index" style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrTerminationAlert" class="alert d-none" role="alert"></div>

        <div class="card mb-3" style="overflow: visible;">
            <div class="card-header hr-termination-header">
                <div class="hr-termination-header__row">
                    @can('create Terminations')
                        <button type="button" class="btn btn-main font-hold fw-bold hr-termination-header__add" id="hrTerminationCreateBtn">
                            <i class="fas fa-plus me-2"></i>{{ __('recruitment.create_termination') }}
                        </button>
                    @endcan
                    <input type="text" id="hrTerminationSearch" class="form-control hr-termination-header__search" placeholder="{{ __('recruitment.search_terminations') }}" aria-label="{{ __('recruitment.search_terminations') }}">
                    <div class="hr-termination-header__filters">
                        <div class="hr-filter-field">
                            <select id="hrTerminationTypeFilter" class="form-select form-select-sm hr-filter-field__select" aria-label="{{ __('recruitment.termination_type') }}">
                                <option value="">{{ __('recruitment.termination_type') }}: {{ __('hr::hr.all') }}</option>
                                <option value="resignation">{{ __('recruitment.resignation') }}</option>
                                <option value="dismissal">{{ __('recruitment.dismissal') }}</option>
                                <option value="death">{{ __('recruitment.death') }}</option>
                                <option value="retirement">{{ __('recruitment.retirement') }}</option>
                            </select>
                        </div>
                        <div class="hr-filter-field">
                            <select id="hrTerminationEmployeeFilter" class="form-select form-select-sm hr-filter-field__select" aria-label="{{ __('recruitment.employee') }}">
                                <option value="">{{ __('recruitment.employee') }}: {{ __('hr::hr.all') }}</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="hr-filter-field">
                            <select id="hrTerminationContractFilter" class="form-select form-select-sm hr-filter-field__select" aria-label="{{ __('recruitment.contract') }}">
                                <option value="">{{ __('recruitment.contract') }}: {{ __('hr::hr.all') }}</option>
                                @foreach ($contracts as $contract)
                                    <option value="{{ $contract->id }}">{{ $contract->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="hr-filter-field hr-filter-field--date">
                            <div class="d-flex align-items-center gap-1">
                                <input type="date" id="hrTerminationDateFrom" class="form-control form-control-sm hr-filter-date" aria-label="{{ __('hr::hr.from_date') }}">
                                <span class="hr-filter-date__sep">—</span>
                                <input type="date" id="hrTerminationDateTo" class="form-control form-control-sm hr-filter-date" aria-label="{{ __('hr::hr.to_date') }}">
                            </div>
                        </div>
                        <span id="hrTerminationFiltersCount" class="hr-filter-count d-none"></span>
                        <button type="button" id="hrTerminationClearFilters" class="btn btn-sm hr-filter-clear d-none"><i class="las la-sync-alt me-1"></i>{{ __('hr::hr.clear_filters') }}</button>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive">
                <div class="mb-2 font-hold fw-bold">
                    {{ __('recruitment.terminations_list') }}
                    <span class="badge bg-primary" id="hrTerminationCount">0</span>
                </div>
                <table class="table table-striped mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th class="font-hold fw-bold">{{ __('recruitment.employee') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.termination_type') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.termination_date') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.reason') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.final_settlement') }}</th>
                            @canany(['edit Terminations', 'delete Terminations'])
                                <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody id="hrTerminationTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrTerminationModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrTerminationModalTitle">{{ __('recruitment.create_termination') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrTerminationForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('recruitment.employee') }} <span class="text-danger">*</span></label>
                            <select name="employee_id" class="form-select font-hold fw-bold js-searchable-filter" required>
                                <option value="">{{ __('recruitment.select_employee') }}</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('recruitment.contract') }}</label>
                            <select name="contract_id" class="form-select font-hold fw-bold">
                                <option value="">{{ __('recruitment.select_contract') }}</option>
                                @foreach ($contracts as $contract)
                                    <option value="{{ $contract->id }}">{{ $contract->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('recruitment.termination_type') }} <span class="text-danger">*</span></label>
                            <select name="termination_type" class="form-select font-hold fw-bold" required>
                                <option value="resignation">{{ __('recruitment.resignation') }}</option>
                                <option value="dismissal">{{ __('recruitment.dismissal') }}</option>
                                <option value="death">{{ __('recruitment.death') }}</option>
                                <option value="retirement">{{ __('recruitment.retirement') }}</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('recruitment.termination_date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="termination_date" class="form-control font-hold fw-bold" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('recruitment.reason') }}</label>
                            <textarea name="reason" class="form-control font-hold fw-bold" rows="3"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('recruitment.final_settlement') }}</label>
                            <input type="text" name="final_settlement" class="form-control font-hold fw-bold" inputmode="decimal">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-main" id="hrTerminationSubmitBtn">{{ __('hr::hr.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    /* Inline filter row — matches the HR employees index header */
    .hr-termination-index .hr-termination-header {
        background: var(--masar-card, #fff);
    }

    .hr-termination-index .hr-termination-header__row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .75rem;
    }

    .hr-termination-index .hr-termination-header__search {
        flex: 1 1 240px;
        min-width: 200px;
        border-radius: .6rem;
    }

    .hr-termination-index .hr-termination-header__filters {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
    }

    .hr-termination-index .hr-filter-field {
        display: flex;
        flex-direction: column;
        gap: .25rem;
        min-width: 140px;
    }

    .hr-termination-index .hr-filter-field--date {
        min-width: auto;
    }

    .hr-termination-index .hr-filter-field__select {
        border-radius: .6rem;
        background-color: var(--masar-card, #fff);
        white-space: nowrap;
    }

    .hr-termination-index .hr-filter-date {
        border-radius: .6rem;
        background-color: var(--masar-card, #fff);
        min-width: 0;
        width: 130px;
    }

    .hr-termination-index .hr-filter-date__sep {
        color: var(--masar-text-muted, #6c757d);
        font-weight: 600;
        flex-shrink: 0;
    }

    .hr-termination-index .hr-filter-count {
        display: inline-flex;
        align-items: center;
        background: var(--masar-mint, #34d3a3);
        color: #fff;
        font-weight: 700;
        font-size: .75rem;
        line-height: 1;
        padding: .4rem .7rem;
        border-radius: 2rem;
        white-space: nowrap;
    }

    .hr-termination-index .hr-filter-clear {
        border: 1px solid color-mix(in srgb, var(--masar-swal-danger, #ef4444) 45%, transparent);
        color: var(--masar-swal-danger, #ef4444);
        background: transparent;
        border-radius: .6rem;
        font-weight: 600;
        white-space: nowrap;
        transition: all .2s ease;
    }

    .hr-termination-index .hr-filter-clear:hover {
        background: color-mix(in srgb, var(--masar-swal-danger, #ef4444) 10%, transparent);
        color: var(--masar-swal-danger, #ef4444);
    }

    @media (max-width: 991.98px) {
        .hr-termination-index .hr-termination-header__add {
            width: 100%;
        }
    }

    @media (max-width: 575.98px) {
        .hr-termination-index .hr-termination-header__filters {
            width: 100%;
        }

        .hr-termination-index .hr-termination-header__filters .hr-filter-field {
            flex: 1 1 auto;
        }

        .hr-termination-index .hr-termination-header__filters .hr-filter-field--date {
            width: 100%;
        }

        .hr-termination-index .hr-filter-date {
            flex: 1 1 auto;
            width: auto;
        }

        .hr-termination-index .hr-filter-clear {
            width: 100%;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    window._hrTerminationsConfig = {
        routes: {
            data: @json(route('recruitment.terminations.data')),
            store: @json(route('recruitment.terminations.store')),
            update: @json(route('recruitment.terminations.update', ['termination' => '__ID__'])),
            destroy: @json(route('recruitment.terminations.destroy', ['termination' => '__ID__']))
        },
        columnCount: {{ auth()->user()->canAny(['edit Terminations', 'delete Terminations']) ? 6 : 5 }},
        canEdit: @json(auth()->user()->can('edit Terminations')),
        canDelete: @json(auth()->user()->can('delete Terminations')),
        csrfToken: @json(csrf_token()),
        typeLabels: {
            resignation: @json(__('recruitment.resignation')),
            dismissal: @json(__('recruitment.dismissal')),
            death: @json(__('recruitment.death')),
            retirement: @json(__('recruitment.retirement'))
        },
        trans: {
            add: @json(__('recruitment.create_termination')),
            editTitle: @json(__('recruitment.edit_termination')),
            save: @json(__('hr::hr.save')),
            update: @json(__('hr::hr.update')),
            empty: @json(__('recruitment.no_terminations_found')),
            confirmDelete: @json(__('recruitment.confirm_delete_termination')),
            saved: @json(__('recruitment.termination_created_successfully')),
            deleted: @json(__('recruitment.termination_deleted_successfully'))
        }
    };
</script>
<script src="{{ asset('js/hr-terminations.js') }}?v={{ @filemtime(public_path('js/hr-terminations.js')) }}"></script>
@endpush
