@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('recruitment.applicants'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('recruitment.recruitment_management')],
            ['label' => __('recruitment.applicants')],
        ],
    ])

    <div style="font-family: var(--masar-font-family); direction: rtl;" class="hr-applicants-index">

        <div id="hrApplicantAlert" class="alert d-none" role="alert"></div>

        {{-- ===== إحصائيات الإعلانات الوظيفية + المتقدمين ===== --}}
        <div class="card mb-3">
            {{-- header: فلاتر فقط --}}
            <div class="card-header hr-applicant-header">
                <div class="hr-applicant-header__row">

                    <input type="text" id="hrApplicantSearch" class="form-control hr-applicant-header__search"
                           placeholder="{{ __('recruitment.search_applicants') }}"
                           aria-label="{{ __('recruitment.search_applicants') }}">

                    <div class="hr-applicant-header__filters">

                        <div class="hr-applicant-filter-field">
                            <select id="hrApplicantJobPostingFilter" class="form-select form-select-sm hr-applicant-filter-select"
                                    aria-label="{{ __('recruitment.job_posting') }}">
                                <option value="">{{ __('recruitment.job_posting') }}: {{ __('recruitment.all_job_postings') }}</option>
                                @foreach ($jobPostings as $jp)
                                    <option value="{{ $jp->id }}">{{ $jp->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="hr-applicant-filter-field">
                            <select id="hrApplicantStatusFilter" class="form-select form-select-sm hr-applicant-filter-select"
                                    aria-label="{{ __('recruitment.status') }}">
                                <option value="">{{ __('recruitment.status') }}: {{ __('recruitment.all_status') }}</option>
                                <option value="pending">{{ __('recruitment.pending') }}</option>
                                <option value="reviewed">{{ __('hr::hr.reviewed') }}</option>
                                <option value="accepted">{{ __('recruitment.accepted') }}</option>
                                <option value="rejected">{{ __('recruitment.rejected') }}</option>
                                <option value="on_hold">{{ __('recruitment.on_hold') }}</option>
                            </select>
                        </div>

                        <div class="hr-applicant-filter-field hr-applicant-filter-field--date">
                            <div class="d-flex align-items-center gap-1">
                                <input type="date" id="hrApplicantDateFrom" class="form-control form-control-sm hr-applicant-filter-date"
                                       aria-label="{{ __('recruitment.from') }}">
                                <span class="hr-applicant-filter-date__sep">—</span>
                                <input type="date" id="hrApplicantDateTo" class="form-control form-control-sm hr-applicant-filter-date"
                                       aria-label="{{ __('recruitment.to') }}">
                            </div>
                        </div>

                        <span id="hrApplicantFiltersCount" class="hr-applicant-filter-count d-none"></span>

                        <button type="button" id="hrApplicantClearFilters" class="btn btn-sm hr-applicant-filter-clear d-none">
                            <i class="las la-sync-alt me-1"></i>{{ __('recruitment.reset_filters') }}
                        </button>

                    </div>
                </div>
            </div>

            {{-- جدول الإعلانات --}}
            <div class="card-body p-0 border-bottom">
                <div class="px-3 pt-2 pb-1 d-flex align-items-center justify-content-between">
                    <span class="font-hold fw-bold small">
                        <i class="las la-bullhorn me-1 text-main"></i>
                        {{ __('recruitment.job_postings') }}
                    </span>
                    <span class="badge bg-secondary" id="hrApplicantPostingCount">0</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 text-center">
                        <thead class="table-light">
                            <tr>
                                <th class="font-hold fw-bold text-start ps-3">{{ __('recruitment.job_title') }}</th>
                                <th class="font-hold fw-bold">{{ __('recruitment.status') }}</th>
                                <th class="font-hold fw-bold">{{ __('recruitment.applicants') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody id="hrApplicantPostingBody">
                            <tr><td colspan="4" class="text-center py-3">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            </td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

    {{-- ===== Create / Edit Modal ===== --}}
    <div class="modal fade" id="hrApplicantModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrApplicantModalTitle">{{ __('recruitment.add_applicant') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrApplicantForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">
                                    {{ __('hr::hr.full_name') }} <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="name" class="form-control font-hold fw-bold" required>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">
                                    {{ __('recruitment.job_posting') }}
                                </label>
                                <select name="job_posting_id" class="form-select font-hold fw-bold">
                                    <option value="">{{ __('recruitment.select_job_posting') }}</option>
                                    @foreach ($jobPostings as $jp)
                                        <option value="{{ $jp->id }}">{{ $jp->title }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">
                                    {{ __('recruitment.status') }} <span class="text-danger">*</span>
                                </label>
                                <select name="status" class="form-select font-hold fw-bold" required>
                                    <option value="pending">{{ __('recruitment.pending') }}</option>
                                    <option value="reviewed">{{ __('hr::hr.reviewed') }}</option>
                                    <option value="accepted">{{ __('recruitment.accepted') }}</option>
                                    <option value="rejected">{{ __('recruitment.rejected') }}</option>
                                    <option value="on_hold">{{ __('recruitment.on_hold') }}</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.cv_file') }}</label>
                                <input type="file" name="cv_file" class="form-control font-hold fw-bold"
                                       accept=".pdf,.doc,.docx">
                                <div class="invalid-feedback"></div>
                                <small id="hrApplicantCurrentFileHint" class="text-muted d-none"></small>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary font-hold" data-bs-dismiss="modal">
                            {{ __('hr::hr.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-main font-hold fw-bold" id="hrApplicantSubmitBtn">
                            {{ __('hr::hr.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    .hr-applicants-index .hr-applicant-header { background: var(--masar-card, #fff); }
    .hr-applicants-index .hr-applicant-header__row {
        display: flex; flex-wrap: wrap; align-items: center; gap: .75rem;
    }
    .hr-applicants-index .hr-applicant-header__search {
        flex: 1 1 220px; min-width: 180px; border-radius: .6rem;
    }
    .hr-applicants-index .hr-applicant-header__filters {
        display: flex; flex-wrap: wrap; align-items: center; gap: .5rem;
    }
    .hr-applicants-index .hr-applicant-filter-field {
        display: flex; flex-direction: column; gap: .25rem; min-width: 160px;
    }
    .hr-applicants-index .hr-applicant-filter-field--date { min-width: auto; }
    .hr-applicants-index .hr-applicant-filter-select {
        border-radius: .6rem; background-color: var(--masar-card, #fff);
    }
    .hr-applicants-index .hr-applicant-filter-date {
        border-radius: .6rem; background-color: var(--masar-card, #fff); width: 130px;
    }
    .hr-applicants-index .hr-applicant-filter-date__sep {
        color: var(--masar-text-muted, #6c757d); font-weight: 600; flex-shrink: 0;
    }
    .hr-applicants-index .hr-applicant-filter-count {
        display: inline-flex; align-items: center;
        background: var(--masar-mint, #34d3a3); color: #fff;
        font-weight: 700; font-size: .75rem; padding: .4rem .7rem;
        border-radius: 2rem; white-space: nowrap;
    }
    .hr-applicants-index .hr-applicant-filter-clear {
        border: 1px solid color-mix(in srgb, var(--masar-swal-danger,#ef4444) 45%, transparent);
        color: var(--masar-swal-danger, #ef4444); background: transparent;
        border-radius: .6rem; font-weight: 600; white-space: nowrap; transition: all .2s;
    }
    .hr-applicants-index .hr-applicant-filter-clear:hover {
        background: color-mix(in srgb, var(--masar-swal-danger,#ef4444) 10%, transparent);
    }
    @media (max-width: 991.98px) {
        .hr-applicants-index .hr-applicant-header__add { width: 100%; }
    }
    @media (max-width: 575.98px) {
        .hr-applicants-index .hr-applicant-header__filters { width: 100%; }
        .hr-applicants-index .hr-applicant-filter-field { flex: 1 1 auto; }
        .hr-applicants-index .hr-applicant-filter-field--date { width: 100%; }
        .hr-applicants-index .hr-applicant-filter-date { flex: 1 1 auto; width: auto; }
        .hr-applicants-index .hr-applicant-filter-clear { width: 100%; }
    }
</style>
@endpush

@push('scripts')
<script>
    window._hrApplicantsConfig = {
        routes: {
            data:         @json(route('recruitment.applicants.data')),
            postingStats: @json(route('recruitment.applicants.posting-stats')),
            store:        @json(route('recruitment.applicants.store')),
            update:       @json(route('recruitment.applicants.update',  ['applicant' => '__ID__'])),
            destroy:      @json(route('recruitment.applicants.destroy', ['applicant' => '__ID__'])),
            download:     @json(route('recruitment.applicants.download', ['applicant' => '__ID__'])),
            jobPosting:   @json(route('recruitment.job-postings.show', ['job_posting' => '__ID__'])),
            latestApplicants: @json(route('recruitment.latest-applicants.index'))
        },
        canEdit:   @json(auth()->user()->can('edit Applicants')),
        canDelete: @json(auth()->user()->can('delete Applicants')),
        csrfToken: @json(csrf_token()),
        statusLabels: {
            pending:  @json(__('recruitment.pending')),
            reviewed: @json(__('hr::hr.reviewed')),
            accepted: @json(__('recruitment.accepted')),
            rejected: @json(__('recruitment.rejected')),
            on_hold:  @json(__('recruitment.on_hold'))
        },
        statusColors: {
            pending: 'warning', reviewed: 'info',
            accepted: 'success', rejected: 'danger', on_hold: 'secondary'
        },
        jpStatusLabels: {
            active:  @json(__('recruitment.active')),
            closed:  @json(__('recruitment.closed')),
            expired: @json(__('recruitment.expired'))
        },
        jpStatusColors: { active: 'success', closed: 'secondary', expired: 'danger' },
        trans: {
            add:           @json(__('recruitment.add_applicant')),
            editTitle:     @json(__('recruitment.edit_applicant')),
            save:          @json(__('hr::hr.save')),
            update:        @json(__('hr::hr.update')),
            empty:         @json(__('recruitment.no_applicants_found')),
            confirmDelete: @json(__('recruitment.confirm_delete_applicant')),
            saved:         @json(__('recruitment.applicant_created_successfully')),
            updated:       @json(__('recruitment.applicant_updated_successfully')),
            deleted:       @json(__('recruitment.applicant_deleted_successfully')),
            hasFile:       @json(__('hr::hr.cv_file')),
            noFile:        @json(__('hr::hr.no_file')),
            download:      @json(__('hr::hr.download_cv')),
            currentFile:   @json(__('hr::hr.current_file')),
            noPosting:     @json(__('recruitment.no_job_posting')),
            filterByPost:  @json(__('recruitment.filter_by_job_posting')),
            applicants:    @json(__('recruitment.applicants')),
            noPostings:    @json(__('recruitment.no_job_postings')),
            errorLoading:  @json(__('recruitment.error_loading_data')),
            errorOccurred: @json(__('recruitment.error_occurred'))
        }
    };
</script>
<script src="{{ asset('js/hr-applicants.js') }}?v={{ @filemtime(public_path('js/hr-applicants.js')) }}"></script>
@endpush
