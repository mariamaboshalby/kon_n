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

    <div style="font-family: var(--masar-font-family); direction: rtl;" class="hr-ja-index">

        <div id="hrJaAlert" class="alert d-none" role="alert"></div>

        {{-- ===== إحصائيات الإعلانات ===== --}}
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="font-hold fw-bold">
                    <i class="las la-bullhorn me-1 text-main"></i>
                    {{ __('recruitment.job_postings') }}
                </span>
                <span class="badge bg-secondary" id="hrJaPostingCount">0</span>
            </div>
            <div class="card-body p-0">
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
                        <tbody id="hrJaPostingBody">
                            <tr><td colspan="4" class="text-center py-3">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            </td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ===== جدول المتقدمين ===== --}}
        <div class="card mb-3">
            <div class="card-header hr-ja-header">
                <div class="hr-ja-header__row">

                    @can('create Applicants')
                        <button type="button" class="btn btn-main font-hold fw-bold" id="hrJaCreateBtn">
                            <i class="fas fa-plus me-2"></i>{{ __('recruitment.add_applicant') }}
                        </button>
                    @else
                        <span></span>
                    @endcan

                    <input type="text" id="hrJaSearch" class="form-control hr-ja-header__search"
                           placeholder="{{ __('recruitment.search_applicants') }}">

                    <div class="hr-ja-header__filters">

                        <div class="hr-ja-filter-field">
                            <select id="hrJaJobPostingFilter" class="form-select form-select-sm">
                                <option value="">{{ __('recruitment.job_posting') }}: {{ __('recruitment.all_job_postings') }}</option>
                                @foreach ($jobPostings as $jp)
                                    <option value="{{ $jp->id }}">{{ $jp->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="hr-ja-filter-field">
                            <select id="hrJaStatusFilter" class="form-select form-select-sm">
                                <option value="">{{ __('recruitment.status') }}: {{ __('recruitment.all_status') }}</option>
                                <option value="pending">{{ __('recruitment.pending') }}</option>
                                <option value="reviewed">{{ __('hr::hr.reviewed') }}</option>
                                <option value="accepted">{{ __('recruitment.accepted') }}</option>
                                <option value="rejected">{{ __('recruitment.rejected') }}</option>
                                <option value="on_hold">{{ __('recruitment.on_hold') }}</option>
                            </select>
                        </div>

                        <div class="hr-ja-filter-field hr-ja-filter-field--date">
                            <div class="d-flex align-items-center gap-1">
                                <input type="date" id="hrJaDateFrom" class="form-control form-control-sm" style="width:130px">
                                <span class="text-muted fw-bold">—</span>
                                <input type="date" id="hrJaDateTo" class="form-control form-control-sm" style="width:130px">
                            </div>
                        </div>

                        <span id="hrJaFiltersCount" class="badge rounded-pill bg-success d-none"></span>

                        <button type="button" id="hrJaClearFilters" class="btn btn-sm btn-outline-danger d-none">
                            <i class="las la-sync-alt me-1"></i>{{ __('recruitment.reset_filters') }}
                        </button>

                    </div>
                </div>
            </div>

            <div class="card-body table-responsive">
                <div class="mb-2 font-hold fw-bold">
                    {{ __('recruitment.applicants') }}
                    <span class="badge bg-primary" id="hrJaCount">0</span>
                </div>
                <table class="table table-striped mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th class="font-hold fw-bold text-start">{{ __('hr::hr.name') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.job_posting') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.status') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.cv_file') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.created_at') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody id="hrJaTableBody"></tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Modal --}}
    <div class="modal fade" id="hrJaModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrJaModalTitle">{{ __('recruitment.add_applicant') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrJaForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.full_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control font-hold fw-bold" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.job_posting') }}</label>
                                <select name="job_posting_id" class="form-select font-hold fw-bold">
                                    <option value="">{{ __('recruitment.select_job_posting') }}</option>
                                    @foreach ($jobPostings as $jp)
                                        <option value="{{ $jp->id }}">{{ $jp->title }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.status') }} <span class="text-danger">*</span></label>
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
                                <input type="file" name="cv_file" class="form-control font-hold fw-bold" accept=".pdf,.doc,.docx">
                                <div class="invalid-feedback"></div>
                                <small id="hrJaCurrentFileHint" class="text-muted d-none"></small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary font-hold" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-main font-hold fw-bold" id="hrJaSubmitBtn">{{ __('hr::hr.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    .hr-ja-index .hr-ja-header { background: var(--masar-card, #fff); }
    .hr-ja-index .hr-ja-header__row { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; }
    .hr-ja-index .hr-ja-header__search { flex: 1 1 220px; min-width: 180px; border-radius: .6rem; }
    .hr-ja-index .hr-ja-header__filters { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
    .hr-ja-index .hr-ja-filter-field { min-width: 160px; }
    .hr-ja-index .hr-ja-filter-field--date { min-width: auto; }
    @media (max-width: 991.98px) { .hr-ja-index .btn.btn-main { width: 100%; } }
</style>
@endpush

@push('scripts')
<script>
    window._hrJaConfig = {
        routes: {
            data:         @json(route('recruitment.job-applicants.data')),
            postingStats: @json(route('recruitment.job-applicants.posting-stats')),
            store:        @json(route('recruitment.job-applicants.store')),
            update:       @json(route('recruitment.job-applicants.update',  ['applicant' => '__ID__'])),
            destroy:      @json(route('recruitment.job-applicants.destroy', ['applicant' => '__ID__'])),
            download:     @json(route('recruitment.job-applicants.download', ['applicant' => '__ID__'])),
            jobPosting:   @json(route('recruitment.job-postings.show', ['job_posting' => '__ID__']))
        },
        canEdit:   @json(auth()->user()->can('edit Applicants')),
        canDelete: @json(auth()->user()->can('delete Applicants')),
        csrfToken: @json(csrf_token()),
        statusLabels:  { pending: @json(__('recruitment.pending')), reviewed: @json(__('hr::hr.reviewed')), accepted: @json(__('recruitment.accepted')), rejected: @json(__('recruitment.rejected')), on_hold: @json(__('recruitment.on_hold')) },
        statusColors:  { pending: 'warning', reviewed: 'info', accepted: 'success', rejected: 'danger', on_hold: 'secondary' },
        jpStatusLabels:{ active: @json(__('recruitment.active')), closed: @json(__('recruitment.closed')), expired: @json(__('recruitment.expired')) },
        jpStatusColors:{ active: 'success', closed: 'secondary', expired: 'danger' },
        trans: {
            add: @json(__('recruitment.add_applicant')), editTitle: @json(__('recruitment.edit_applicant')),
            save: @json(__('hr::hr.save')), update: @json(__('hr::hr.update')),
            empty: @json(__('recruitment.no_applicants_found')),
            confirmDelete: @json(__('recruitment.confirm_delete_applicant')),
            saved: @json(__('recruitment.applicant_created_successfully')),
            updated: @json(__('recruitment.applicant_updated_successfully')),
            deleted: @json(__('recruitment.applicant_deleted_successfully')),
            hasFile: @json(__('hr::hr.cv_file')), noFile: @json(__('hr::hr.no_file')),
            download: @json(__('hr::hr.download_cv')), currentFile: @json(__('hr::hr.current_file')),
            noPosting: @json(__('recruitment.no_job_posting')),
            filterByPost: @json(__('recruitment.filter_by_job_posting')),
            errorLoading: @json(__('recruitment.error_loading_data')),
            errorOccurred: @json(__('recruitment.error_occurred'))
        }
    };
</script>
<script src="{{ asset('js/hr-job-applicants.js') }}?v={{ @filemtime(public_path('js/hr-job-applicants.js')) }}"></script>
@endpush
