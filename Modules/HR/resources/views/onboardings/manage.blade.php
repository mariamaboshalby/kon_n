@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('recruitment.onboardings'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('recruitment.recruitment_management')],
            ['label' => __('recruitment.onboardings')],
        ],
    ])

    <div class="hr-onboarding-index" style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrOnboardingAlert" class="alert d-none" role="alert"></div>
        <div class="card mb-3" style="overflow: visible;">
            <div class="card-header hr-onboarding-header">
                <div class="hr-onboarding-header__row">
                    @can('create Onboardings')
                        <button type="button" class="btn btn-main font-hold fw-bold hr-onboarding-header__add" id="hrOnboardingCreateBtn">
                            <i class="fas fa-plus me-2"></i>{{ __('recruitment.create_onboarding') }}
                        </button>
                    @endcan
                    <input type="text" id="hrOnboardingSearch" class="form-control hr-onboarding-header__search" placeholder="{{ __('recruitment.search_onboardings') }}" aria-label="{{ __('recruitment.search_onboardings') }}">
                    <div class="hr-onboarding-header__filters">
                        <div class="hr-filter-field">
                            <select id="hrOnboardingStatusFilter" class="form-select form-select-sm hr-filter-field__select" aria-label="{{ __('recruitment.status') }}">
                                <option value="">{{ __('recruitment.status') }}: {{ __('hr::hr.all') }}</option>
                                <option value="pending">{{ __('recruitment.pending') }}</option>
                                <option value="in_progress">{{ __('recruitment.in_progress') }}</option>
                                <option value="completed">{{ __('recruitment.completed') }}</option>
                                <option value="cancelled">{{ __('recruitment.cancelled') }}</option>
                            </select>
                        </div>
                        <div class="hr-filter-field">
                            <select id="hrOnboardingContractFilter" class="form-select form-select-sm hr-filter-field__select" aria-label="{{ __('recruitment.contract') }}">
                                <option value="">{{ __('recruitment.contract') }}: {{ __('hr::hr.all') }}</option>
                                @foreach ($contracts as $contract)
                                    <option value="{{ $contract->id }}">{{ $contract->name ?? (__('recruitment.contract') . ' #' . $contract->id) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="hr-filter-field">
                            <select id="hrOnboardingCandidateFilter" class="form-select form-select-sm hr-filter-field__select" aria-label="{{ __('recruitment.candidate') }}">
                                <option value="">{{ __('recruitment.candidate') }}: {{ __('hr::hr.all') }}</option>
                                @foreach ($cvs as $cv)
                                    <option value="{{ $cv->id }}">{{ $cv->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="hr-filter-field hr-filter-field--date">
                            <div class="d-flex align-items-center gap-1">
                                <input type="date" id="hrOnboardingDateFrom" class="form-control form-control-sm hr-filter-date" aria-label="{{ __('hr::hr.from_date') }}">
                                <span class="hr-filter-date__sep">—</span>
                                <input type="date" id="hrOnboardingDateTo" class="form-control form-control-sm hr-filter-date" aria-label="{{ __('hr::hr.to_date') }}">
                            </div>
                        </div>
                        <span id="hrOnboardingFiltersCount" class="hr-filter-count d-none"></span>
                        <button type="button" id="hrOnboardingClearFilters" class="btn btn-sm hr-filter-clear d-none"><i class="las la-sync-alt me-1"></i>{{ __('hr::hr.clear_filters') }}</button>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive">
                <div class="mb-2 font-hold fw-bold">{{ __('recruitment.onboardings_list') }} <span class="badge bg-primary" id="hrOnboardingCount">0</span></div>
                <table class="table table-striped mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th class="font-hold fw-bold">{{ __('recruitment.candidate') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.contract') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.status') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.start_date') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.completion_date') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.documents') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody id="hrOnboardingTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrOnboardingModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrOnboardingModalTitle">{{ __('recruitment.create_onboarding') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrOnboardingForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.contract') }}</label>
                                <select name="contract_id" class="form-select font-hold fw-bold">
                                    <option value="">{{ __('recruitment.select_contract') }}</option>
                                    @foreach ($contracts as $contract)
                                        <option value="{{ $contract->id }}">{{ $contract->name ?? (__('recruitment.contract') . ' #' . $contract->id) }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.candidate') }} <span class="text-danger">*</span></label>
                                <select name="cv_id" class="form-select font-hold fw-bold" required>
                                    <option value="">{{ __('recruitment.select_cv') }}</option>
                                    @foreach ($cvs as $cv)
                                        <option value="{{ $cv->id }}">{{ $cv->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.interview') }}</label>
                                <select name="interview_id" class="form-select font-hold fw-bold">
                                    <option value="">{{ __('recruitment.select_interview') }}</option>
                                    @php
                                        $cvNames = $cvs->pluck('name', 'id');
                                    @endphp
                                    @foreach ($interviews as $interview)
                                        <option value="{{ $interview->id }}">
                                            {{ $cvNames[$interview->cv_id] ?? (__('recruitment.interview') . ' #' . $interview->id) }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.status') }} <span class="text-danger">*</span></label>
                                <select name="status" class="form-select font-hold fw-bold" required>
                                    <option value="pending">{{ __('recruitment.pending') }}</option>
                                    <option value="in_progress">{{ __('recruitment.in_progress') }}</option>
                                    <option value="completed">{{ __('recruitment.completed') }}</option>
                                    <option value="cancelled">{{ __('recruitment.cancelled') }}</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.start_date') }}</label>
                                <input type="date" name="start_date" class="form-control font-hold fw-bold">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.completion_date') }}</label>
                                <input type="date" name="completion_date" class="form-control font-hold fw-bold">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.notes') }}</label>
                                <textarea name="notes" class="form-control font-hold fw-bold" rows="3"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label font-hold fw-bold mb-0">{{ __('recruitment.checklist') }}</label>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="hrOnboardingAddChecklist">
                                        <i class="las la-plus"></i> {{ __('recruitment.add_checklist_item') }}
                                    </button>
                                </div>
                                <div id="hrOnboardingChecklistWrap"></div>
                            </div>
                            <div class="col-12 mb-3">
                                <div id="hrOnboardingExistingDocs" class="border rounded p-2 mb-2 d-none"></div>
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.documents') }}</label>
                                <input type="file" name="documents[]" class="form-control font-hold fw-bold" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                <small class="text-muted">{{ __('recruitment.upload_document') }}</small>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-main" id="hrOnboardingSubmitBtn">{{ __('hr::hr.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    /* Inline filter row — matches the HR employees index header */
    .hr-onboarding-index .hr-onboarding-header {
        background: var(--masar-card, #fff);
    }

    .hr-onboarding-index .hr-onboarding-header__row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .75rem;
    }

    .hr-onboarding-index .hr-onboarding-header__search {
        flex: 1 1 240px;
        min-width: 200px;
        border-radius: .6rem;
    }

    .hr-onboarding-index .hr-onboarding-header__filters {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
    }

    .hr-onboarding-index .hr-filter-field {
        display: flex;
        flex-direction: column;
        gap: .25rem;
        min-width: 140px;
    }

    .hr-onboarding-index .hr-filter-field--date {
        min-width: auto;
    }

    .hr-onboarding-index .hr-filter-field__select {
        border-radius: .6rem;
        background-color: var(--masar-card, #fff);
        white-space: nowrap;
    }

    .hr-onboarding-index .hr-filter-date {
        border-radius: .6rem;
        background-color: var(--masar-card, #fff);
        min-width: 0;
        width: 130px;
    }

    .hr-onboarding-index .hr-filter-date__sep {
        color: var(--masar-text-muted, #6c757d);
        font-weight: 600;
        flex-shrink: 0;
    }

    .hr-onboarding-index .hr-filter-count {
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

    .hr-onboarding-index .hr-filter-clear {
        border: 1px solid color-mix(in srgb, var(--masar-swal-danger, #ef4444) 45%, transparent);
        color: var(--masar-swal-danger, #ef4444);
        background: transparent;
        border-radius: .6rem;
        font-weight: 600;
        white-space: nowrap;
        transition: all .2s ease;
    }

    .hr-onboarding-index .hr-filter-clear:hover {
        background: color-mix(in srgb, var(--masar-swal-danger, #ef4444) 10%, transparent);
        color: var(--masar-swal-danger, #ef4444);
    }

    @media (max-width: 991.98px) {
        .hr-onboarding-index .hr-onboarding-header__add {
            width: 100%;
        }
    }

    @media (max-width: 575.98px) {
        .hr-onboarding-index .hr-onboarding-header__filters {
            width: 100%;
        }

        .hr-onboarding-index .hr-onboarding-header__filters .hr-filter-field {
            flex: 1 1 auto;
        }

        .hr-onboarding-index .hr-onboarding-header__filters .hr-filter-field--date {
            width: 100%;
        }

        .hr-onboarding-index .hr-filter-date {
            flex: 1 1 auto;
            width: auto;
        }

        .hr-onboarding-index .hr-filter-clear {
            width: 100%;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    window._hrOnboardingsConfig = {
        routes: {
            data: @json(route('recruitment.onboardings.data')),
            store: @json(route('recruitment.onboardings.store')),
            update: @json(route('recruitment.onboardings.update', ['onboarding' => '__ID__'])),
            destroy: @json(route('recruitment.onboardings.destroy', ['onboarding' => '__ID__'])),
            documentDownload: @json(route('recruitment.onboardings.documents.download', ['onboarding' => '__OID__', 'mediaId' => '__MID__'])),
            documentDestroy: @json(route('recruitment.onboardings.documents.destroy', ['onboarding' => '__OID__', 'mediaId' => '__MID__']))
        },
        columnCount: 7,
        canEdit: @json(auth()->user()->can('edit Onboardings')),
        canDelete: @json(auth()->user()->can('delete Onboardings')),
        csrfToken: @json(csrf_token()),
        statusLabels: {
            pending: @json(__('recruitment.pending')),
            in_progress: @json(__('recruitment.in_progress')),
            completed: @json(__('recruitment.completed')),
            cancelled: @json(__('recruitment.cancelled'))
        },
        trans: {
            add: @json(__('recruitment.create_onboarding')),
            editTitle: @json(__('recruitment.edit_onboarding')),
            save: @json(__('hr::hr.save')),
            update: @json(__('hr::hr.update')),
            empty: @json(__('recruitment.no_onboardings_found')),
            confirmDelete: @json(__('recruitment.confirm_delete_onboarding')),
            confirmDeleteDocument: @json(__('recruitment.confirm_delete_document')),
            saved: @json(__('recruitment.onboarding_created_successfully')),
            deleted: @json(__('recruitment.onboarding_deleted_successfully')),
            documentDeleted: @json(__('recruitment.document_deleted_successfully')),
            deleteDocument: @json(__('recruitment.delete_document')),
            download: @json(__('recruitment.download_document')),
            documents: @json(__('recruitment.documents')),
            checklistItem: @json(__('recruitment.checklist_item')),
            removeChecklist: @json(__('recruitment.remove_checklist_item')),
            completed: @json(__('recruitment.completed')),
            employeeCreated: @json(__('recruitment.employee_created')),
            loadError: @json(__('messages.error_occurred'))
        }
    };
</script>
<script src="{{ asset('js/hr-onboardings.js') }}?v={{ @filemtime(public_path('js/hr-onboardings.js')) }}"></script>
@endpush
