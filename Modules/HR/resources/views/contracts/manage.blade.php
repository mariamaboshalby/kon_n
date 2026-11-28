@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('recruitment.contracts'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('recruitment.recruitment_management')],
            ['label' => __('recruitment.contracts')],
        ],
    ])

    <div class="hr-contract-index" style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrContractAlert" class="alert d-none" role="alert"></div>
        <div class="card mb-3" style="overflow: visible;">
            <div class="card-header hr-contract-header">
                <div class="hr-contract-header__row">
                    @can('create Contracts')
                        <button type="button" class="btn btn-main font-hold fw-bold hr-contract-header__add" id="hrContractCreateBtn">
                            <i class="fas fa-plus me-2"></i>{{ __('hr::hr.add_contract') }}
                        </button>
                    @endcan
                    <input type="text" id="hrContractSearch" class="form-control hr-contract-header__search" placeholder="{{ __('hr::hr.search_by_title') }}" aria-label="{{ __('hr::hr.search_by_title') }}">
                    <div class="hr-contract-header__filters">
                        <div class="hr-filter-field">
                            <select id="hrContractStatusFilter" class="form-select form-select-sm hr-filter-field__select" aria-label="{{ __('hr::hr.status') }}">
                                <option value="">{{ __('hr::hr.status') }}: {{ __('hr::hr.all') }}</option>
                                <option value="pending">{{ __('hr::hr.status_pending') }}</option>
                                <option value="active">{{ __('hr::hr.status_active') }}</option>
                                <option value="expired">{{ __('hr::hr.status_expired') }}</option>
                                <option value="terminated">{{ __('hr::hr.status_terminated') }}</option>
                            </select>
                        </div>
                        <div class="hr-filter-field">
                            <select id="hrContractTypeFilter" class="form-select form-select-sm hr-filter-field__select" aria-label="{{ __('recruitment.contract_type') }}">
                                <option value="">{{ __('recruitment.contract_type') }}: {{ __('hr::hr.all') }}</option>
                                @foreach ($contractTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="hr-filter-field">
                            <select id="hrContractJobFilter" class="form-select form-select-sm hr-filter-field__select" aria-label="{{ __('recruitment.job') }}">
                                <option value="">{{ __('recruitment.job') }}: {{ __('hr::hr.all') }}</option>
                                @foreach ($jobs as $job)
                                    <option value="{{ $job->id }}">{{ $job->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="hr-filter-field">
                            <select id="hrContractEmployeeFilter" class="form-select form-select-sm hr-filter-field__select" aria-label="{{ __('recruitment.employee') }}">
                                <option value="">{{ __('recruitment.employee') }}: {{ __('hr::hr.all') }}</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="hr-filter-field hr-filter-field--date">
                            <div class="d-flex align-items-center gap-1">
                                <input type="date" id="hrContractDateFrom" class="form-control form-control-sm hr-filter-date" aria-label="{{ __('hr::hr.from_date') }}">
                                <span class="hr-filter-date__sep">—</span>
                                <input type="date" id="hrContractDateTo" class="form-control form-control-sm hr-filter-date" aria-label="{{ __('hr::hr.to_date') }}">
                            </div>
                        </div>
                        <span id="hrContractFiltersCount" class="hr-filter-count d-none"></span>
                        <button type="button" id="hrContractClearFilters" class="btn btn-sm hr-filter-clear d-none"><i class="las la-sync-alt me-1"></i>{{ __('hr::hr.clear_filters') }}</button>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive">
                <div class="mb-2 font-hold fw-bold">{{ __('recruitment.contracts') }} <span class="badge bg-primary" id="hrContractCount">0</span></div>
                <table class="table table-striped mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th class="font-hold fw-bold">{{ __('hr::hr.name') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.employee') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.contract_type') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.start_date') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.end_date') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.trial_period') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.contract_duration') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.status') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody id="hrContractTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        #hrContractModal,
        #hrContractViewModal {
            overflow: hidden;
        }
        #hrContractModal .modal-dialog {
            max-width: min(1200px, 96vw);
            max-height: calc(100vh - 30px);
            margin: 15px auto;
            display: flex;
        }
        #hrContractModal .modal-content {
            width: 100%;
            max-height: calc(100vh - 30px);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        #hrContractModal form#hrContractForm {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
        }
        #hrContractModal .modal-header,
        #hrContractModal .modal-footer {
            flex: 0 0 auto;
        }
        #hrContractModal .modal-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        #hrContractViewModal .modal-dialog {
            max-height: calc(100vh - 30px);
            margin: 15px auto;
            display: flex;
        }
        #hrContractViewModal .modal-content {
            max-height: calc(100vh - 30px);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        #hrContractViewModal .modal-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
        }
    </style>

    <div class="modal fade" id="hrContractModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrContractModalTitle">{{ __('hr::hr.add_contract') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrContractForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control font-hold fw-bold" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.employee') }} <span class="text-danger">*</span></label>
                                <select name="employee_id" class="form-select font-hold fw-bold js-searchable-filter" required>
                                    <option value="">{{ __('recruitment.select_employee') }}</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.contract_type') }} <span class="text-danger">*</span></label>
                                <select name="contract_type_id" class="form-select font-hold fw-bold" required>
                                    <option value="">{{ __('hr::hr.select_option') }}</option>
                                    @foreach ($contractTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.job') }} <span class="text-danger">*</span></label>
                                <select name="job_id" class="form-select font-hold fw-bold" required>
                                    <option value="">{{ __('recruitment.select_job') }}</option>
                                    @foreach ($jobs as $job)
                                        <option value="{{ $job->id }}">{{ $job->title }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.start_date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="contract_start_date" class="form-control font-hold fw-bold" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.end_date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="contract_end_date" class="form-control font-hold fw-bold" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.trial_period') }} <span class="text-muted">({{ __('hr::hr.days') }})</span></label>
                                <input type="text" name="trial_period" class="form-control font-hold fw-bold" inputmode="numeric" pattern="[0-9]*" autocomplete="off">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.status') }} <span class="text-danger">*</span></label>
                                <select name="status" class="form-select font-hold fw-bold" required>
                                    <option value="pending">{{ __('hr::hr.status_pending') }}</option>
                                    <option value="active" selected>{{ __('hr::hr.status_active') }}</option>
                                    <option value="expired">{{ __('hr::hr.status_expired') }}</option>
                                    <option value="terminated">{{ __('hr::hr.status_terminated') }}</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.fixed_work_hours') }}</label>
                                <input type="text" name="fixed_work_hours" class="form-control font-hold fw-bold" inputmode="decimal">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.additional_work_hours') }}</label>
                                <input type="text" name="additional_work_hours" class="form-control font-hold fw-bold" inputmode="decimal">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.monthly_holidays') }}</label>
                                <input type="text" name="monthly_holidays" class="form-control font-hold fw-bold" inputmode="decimal">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.monthly_sick_days') }}</label>
                                <input type="text" name="monthly_sick_days" class="form-control font-hold fw-bold" inputmode="decimal">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.job_description') }}</label>
                                <textarea name="job_description" class="form-control font-hold fw-bold" rows="2"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.information') }}</label>
                                <textarea name="information" class="form-control font-hold fw-bold" rows="2"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label font-hold fw-bold mb-0">{{ __('hr::hr.contract_points') }}</label>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="hrContractAddPoint">
                                        <i class="las la-plus"></i> {{ __('hr::hr.add_point') }}
                                    </button>
                                </div>
                                <div id="hrContractPointsWrap"></div>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label font-hold fw-bold mb-0">{{ __('hr::hr.salary_points') }}</label>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="hrContractAddSalary">
                                        <i class="las la-plus"></i> {{ __('hr::hr.add_point') }}
                                    </button>
                                </div>
                                <div id="hrSalaryPointsWrap"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-main" id="hrContractSubmitBtn">{{ __('hr::hr.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrContractViewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold">{{ __('hr::hr.view_contract') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="hrContractViewBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.close') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    /* Inline filter row — matches the HR employees index header */
    .hr-contract-index .hr-contract-header {
        background: var(--masar-card, #fff);
    }

    .hr-contract-index .hr-contract-header__row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .75rem;
    }

    .hr-contract-index .hr-contract-header__search {
        flex: 1 1 240px;
        min-width: 200px;
        border-radius: .6rem;
    }

    .hr-contract-index .hr-contract-header__filters {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
    }

    .hr-contract-index .hr-filter-field {
        display: flex;
        flex-direction: column;
        gap: .25rem;
        min-width: 140px;
    }

    .hr-contract-index .hr-filter-field--date {
        min-width: auto;
    }

    .hr-contract-index .hr-filter-field__select {
        border-radius: .6rem;
        background-color: var(--masar-card, #fff);
        white-space: nowrap;
    }

    .hr-contract-index .hr-filter-date {
        border-radius: .6rem;
        background-color: var(--masar-card, #fff);
        min-width: 0;
        width: 130px;
    }

    .hr-contract-index .hr-filter-date__sep {
        color: var(--masar-text-muted, #6c757d);
        font-weight: 600;
        flex-shrink: 0;
    }

    .hr-contract-index .hr-filter-count {
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

    .hr-contract-index .hr-filter-clear {
        border: 1px solid color-mix(in srgb, var(--masar-swal-danger, #ef4444) 45%, transparent);
        color: var(--masar-swal-danger, #ef4444);
        background: transparent;
        border-radius: .6rem;
        font-weight: 600;
        white-space: nowrap;
        transition: all .2s ease;
    }

    .hr-contract-index .hr-filter-clear:hover {
        background: color-mix(in srgb, var(--masar-swal-danger, #ef4444) 10%, transparent);
        color: var(--masar-swal-danger, #ef4444);
    }

    @media (max-width: 991.98px) {
        .hr-contract-index .hr-contract-header__add {
            width: 100%;
        }
    }

    @media (max-width: 575.98px) {
        .hr-contract-index .hr-contract-header__filters {
            width: 100%;
        }

        .hr-contract-index .hr-contract-header__filters .hr-filter-field {
            flex: 1 1 auto;
        }

        .hr-contract-index .hr-contract-header__filters .hr-filter-field--date {
            width: 100%;
        }

        .hr-contract-index .hr-filter-date {
            flex: 1 1 auto;
            width: auto;
        }

        .hr-contract-index .hr-filter-clear {
            width: 100%;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    window._hrContractsConfig = {
        routes: {
            data: @json(route('recruitment.contracts.data')),
            store: @json(route('recruitment.contracts.store')),
            update: @json(route('recruitment.contracts.update', ['contract' => '__ID__'])),
            destroy: @json(route('recruitment.contracts.destroy', ['contract' => '__ID__']))
        },
        columnCount: 9,
        canEdit: @json(auth()->user()->can('edit Contracts')),
        canDelete: @json(auth()->user()->can('delete Contracts')),
        csrfToken: @json(csrf_token()),
        trans: {
            add: @json(__('hr::hr.add_contract')),
            editTitle: @json(__('hr::hr.edit_contract')),
            save: @json(__('hr::hr.save')),
            update: @json(__('hr::hr.update')),
            empty: @json(__('recruitment.no_contracts_found')),
            confirmDelete: @json(__('hr::hr.confirm_delete_contract')),
            saved: @json(__('recruitment.contract_created_successfully')),
            deleted: @json(__('recruitment.contract_deleted_successfully')),
            pointName: @json(__('hr::hr.point_name')),
            information: @json(__('hr::hr.information')),
            name: @json(__('hr::hr.name')),
            employee: @json(__('recruitment.employee')),
            contractType: @json(__('recruitment.contract_type')),
            job: @json(__('recruitment.job')),
            startDate: @json(__('recruitment.start_date')),
            endDate: @json(__('recruitment.end_date')),
            jobDescription: @json(__('recruitment.job_description')),
            contractPoints: @json(__('hr::hr.contract_points')),
            salaryPoints: @json(__('hr::hr.salary_points')),
            trialPeriod: @json(__('hr::hr.trial_period')),
            duration: @json(__('hr::hr.contract_duration')),
            status: @json(__('hr::hr.status')),
            days: @json(__('hr::hr.days')),
            statusLabels: {
                pending: @json(__('hr::hr.status_pending')),
                active: @json(__('hr::hr.status_active')),
                expired: @json(__('hr::hr.status_expired')),
                terminated: @json(__('hr::hr.status_terminated'))
            }
        }
    };
</script>
<script src="{{ asset('js/hr-contracts.js') }}?v={{ filemtime(public_path('js/hr-contracts.js')) }}"></script>
@endpush
