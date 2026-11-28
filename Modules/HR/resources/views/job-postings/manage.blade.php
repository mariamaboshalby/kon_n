@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('recruitment.job_postings'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('recruitment.recruitment_management')],
            ['label' => __('recruitment.job_postings')],
        ],
    ])

    <div style="font-family: var(--masar-font-family); direction: rtl;" class="hr-jp-index">
        <div id="hrJobPostingAlert" class="alert d-none" role="alert"></div>

        <div class="card mb-3">
            <div class="card-header hr-jp-header">
                <div class="hr-jp-header__row">
                    @can('create Job Postings')
                        <button type="button" class="btn btn-main font-hold fw-bold hr-jp-header__add" id="hrJobPostingCreateBtn">
                            <i class="fas fa-plus me-2"></i>{{ __('recruitment.create_job_posting') }}
                        </button>
                    @else
                        <span></span>
                    @endcan

                    <input type="text" id="hrJobPostingSearch" class="form-control hr-jp-header__search"
                           placeholder="{{ __('recruitment.search_job_postings') }}"
                           aria-label="{{ __('recruitment.search_job_postings') }}">

                    <div class="hr-jp-header__filters">
                        <div class="hr-jp-filter-field">
                            <select id="hrJobPostingStatusFilter" class="form-select form-select-sm hr-jp-filter-select"
                                    aria-label="{{ __('recruitment.status') }}">
                                <option value="">{{ __('recruitment.status') }}: {{ __('recruitment.all_status') }}</option>
                                <option value="active">{{ __('recruitment.active') }}</option>
                                <option value="closed">{{ __('recruitment.closed') }}</option>
                                <option value="expired">{{ __('recruitment.expired') }}</option>
                            </select>
                        </div>

                        <div class="hr-jp-filter-field">
                            <select id="hrJobPostingJobFilter" class="form-select form-select-sm hr-jp-filter-select"
                                    aria-label="{{ __('recruitment.job') }}">
                                <option value="">{{ __('recruitment.job') }}: {{ __('recruitment.all_jobs') }}</option>
                                @foreach ($jobs as $job)
                                    <option value="{{ $job->id }}">{{ $job->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="hr-jp-filter-field hr-jp-filter-field--date">
                            <div class="d-flex align-items-center gap-1">
                                <input type="date" id="hrJobPostingDateFrom" class="form-control form-control-sm hr-jp-filter-date"
                                       aria-label="{{ __('recruitment.from') }}">
                                <span class="hr-jp-filter-date__sep">—</span>
                                <input type="date" id="hrJobPostingDateTo" class="form-control form-control-sm hr-jp-filter-date"
                                       aria-label="{{ __('recruitment.to') }}">
                            </div>
                        </div>

                        <span id="hrJobPostingFiltersCount" class="hr-jp-filter-count d-none"></span>

                        <button type="button" id="hrJobPostingClearFilters" class="btn btn-sm hr-jp-filter-clear d-none">
                            <i class="las la-sync-alt me-1"></i>{{ __('recruitment.reset_filters') }}
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive">
                <div class="mb-2 font-hold fw-bold">
                    {{ __('recruitment.job_postings_list') }}
                    <span class="badge bg-primary" id="hrJobPostingCount">0</span>
                </div>
                <table class="table table-striped mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th class="font-hold fw-bold">{{ __('recruitment.job_title') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.job') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.status') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.start_date') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.end_date') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.applicants') }}</th>
                            @canany(['edit Job Postings', 'delete Job Postings'])
                                <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody id="hrJobPostingTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrJobPostingModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrJobPostingModalTitle">{{ __('recruitment.create_job_posting') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrJobPostingForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('recruitment.job_title') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control font-hold fw-bold" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.job') }}</label>
                                <select name="job_id" class="form-select font-hold fw-bold">
                                    <option value="">{{ __('recruitment.select_job') }}</option>
                                    @foreach ($jobs as $job)
                                        <option value="{{ $job->id }}">{{ $job->title }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.qualification_type') }}</label>
                                <select name="qualification_type" class="form-select font-hold fw-bold">
                                    <option value="">{{ __('recruitment.select_qualification_type') }}</option>
                                    <option value="diploma">{{ __('recruitment.qualification_diploma') }}</option>
                                    <option value="bachelor">{{ __('recruitment.qualification_bachelor') }}</option>
                                    <option value="master">{{ __('recruitment.qualification_master') }}</option>
                                    <option value="phd">{{ __('recruitment.qualification_phd') }}</option>
                                    <option value="other">{{ __('recruitment.qualification_other') }}</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.status') }} <span class="text-danger">*</span></label>
                                <select name="status" class="form-select font-hold fw-bold" required>
                                    <option value="active">{{ __('recruitment.active') }}</option>
                                    <option value="closed">{{ __('recruitment.closed') }}</option>
                                    <option value="expired">{{ __('recruitment.expired') }}</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.start_date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control font-hold fw-bold" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.end_date') }}</label>
                                <input type="date" name="end_date" class="form-control font-hold fw-bold">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('recruitment.job_description') }}</label>
                            <textarea name="description" class="form-control font-hold fw-bold" rows="3"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('recruitment.requirements') }}</label>
                            <textarea name="requirements" class="form-control font-hold fw-bold" rows="3"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('recruitment.benefits') }}</label>
                            <textarea name="benefits" class="form-control font-hold fw-bold" rows="3"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-main" id="hrJobPostingSubmitBtn">{{ __('hr::hr.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    /* Inline filter row — matches the HR employees index header */
    .hr-jp-index .hr-jp-header {
        background: var(--masar-card, #fff);
    }

    .hr-jp-index .hr-jp-header__row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .75rem;
    }

    .hr-jp-index .hr-jp-header__search {
        flex: 1 1 260px;
        min-width: 220px;
        border-radius: .6rem;
    }

    .hr-jp-index .hr-jp-header__filters {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
    }

    .hr-jp-index .hr-jp-filter-field {
        display: flex;
        flex-direction: column;
        gap: .25rem;
        min-width: 150px;
    }

    .hr-jp-index .hr-jp-filter-field--date {
        min-width: auto;
    }

    .hr-jp-index .hr-jp-filter-select {
        border-radius: .6rem;
        background-color: var(--masar-card, #fff);
        white-space: nowrap;
    }

    .hr-jp-index .hr-jp-filter-date {
        border-radius: .6rem;
        background-color: var(--masar-card, #fff);
        min-width: 0;
        width: 130px;
    }

    .hr-jp-index .hr-jp-filter-date__sep {
        color: var(--masar-text-muted, #6c757d);
        font-weight: 600;
        flex-shrink: 0;
    }

    .hr-jp-index .hr-jp-filter-count {
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

    .hr-jp-index .hr-jp-filter-clear {
        border: 1px solid color-mix(in srgb, var(--masar-swal-danger, #ef4444) 45%, transparent);
        color: var(--masar-swal-danger, #ef4444);
        background: transparent;
        border-radius: .6rem;
        font-weight: 600;
        white-space: nowrap;
        transition: all .2s ease;
    }

    .hr-jp-index .hr-jp-filter-clear:hover {
        background: color-mix(in srgb, var(--masar-swal-danger, #ef4444) 10%, transparent);
        color: var(--masar-swal-danger, #ef4444);
    }

    @media (max-width: 991.98px) {
        .hr-jp-index .hr-jp-header__add {
            width: 100%;
        }
    }

    @media (max-width: 575.98px) {
        .hr-jp-index .hr-jp-header__filters {
            width: 100%;
        }

        .hr-jp-index .hr-jp-header__filters .hr-jp-filter-field {
            flex: 1 1 auto;
        }

        .hr-jp-index .hr-jp-header__filters .hr-jp-filter-field--date {
            width: 100%;
        }

        .hr-jp-index .hr-jp-filter-date {
            flex: 1 1 auto;
            width: auto;
        }

        .hr-jp-index .hr-jp-filter-clear {
            width: 100%;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    window._hrJobPostingsConfig = {
        routes: {
            data: @json(route('recruitment.job-postings.data')),
            store: @json(route('recruitment.job-postings.store')),
            update: @json(route('recruitment.job-postings.update', ['job_posting' => '__ID__'])),
            destroy: @json(route('recruitment.job-postings.destroy', ['job_posting' => '__ID__'])),
            latestApplicants: @json(route('recruitment.latest-applicants.index'))
        },
        columnCount: {{ auth()->user()->canAny(['edit Job Postings', 'delete Job Postings']) ? 7 : 6 }},
        canEdit: @json(auth()->user()->can('edit Job Postings')),
        canDelete: @json(auth()->user()->can('delete Job Postings')),
        csrfToken: @json(csrf_token()),
        statusLabels: {
            active: @json(__('recruitment.active')),
            closed: @json(__('recruitment.closed')),
            expired: @json(__('recruitment.expired'))
        },
        trans: {
            add: @json(__('recruitment.create_job_posting')),
            editTitle: @json(__('recruitment.edit_job_posting')),
            save: @json(__('hr::hr.save')),
            update: @json(__('hr::hr.update')),
            empty: @json(__('recruitment.no_job_postings_found')),
            confirmDelete: @json(__('recruitment.confirm_delete_job_posting')),
            saved: @json(__('recruitment.job_posting_created_successfully')),
            updated: @json(__('recruitment.job_posting_updated_successfully')),
            deleted: @json(__('recruitment.job_posting_deleted_successfully')),
            noJob: @json(__('recruitment.no_job_assigned')),
            noDeadline: @json(__('recruitment.no_deadline')),
            loadError: @json(__('recruitment.error_loading_data')),
            requestError: @json(__('recruitment.error_occurred')),
            viewApplicants: @json(__('recruitment.applicants')),
            weekdays: [
                @json(__('recruitment.day_sun')),
                @json(__('recruitment.day_mon')),
                @json(__('recruitment.day_tue')),
                @json(__('recruitment.day_wed')),
                @json(__('recruitment.day_thu')),
                @json(__('recruitment.day_fri')),
                @json(__('recruitment.day_sat'))
            ]
        }
    };
</script>
<script src="{{ asset('js/hr-job-postings.js') }}?v={{ @filemtime(public_path('js/hr-job-postings.js')) }}"></script>
@endpush
