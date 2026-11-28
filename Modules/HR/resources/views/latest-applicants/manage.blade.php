@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('recruitment.latest_applicants'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('recruitment.recruitment_management')],
            ['label' => __('recruitment.latest_applicants')],
        ],
    ])

    <div style="font-family: var(--masar-font-family); direction: rtl;" class="hr-la-index">

        <div id="hrLaAlert" class="alert d-none" role="alert"></div>

        <div class="card mb-3">
            <div class="card-header hr-la-header">
                <div class="hr-la-header__row">

                    {{-- زر عرض كل السير الذاتية --}}
                    @can('view CVs')
                        <a href="{{ route('recruitment.cvs.index') }}" class="btn btn-outline-main font-hold fw-bold">
                            <i class="las la-file-alt me-1"></i>{{ __('recruitment.cvs') }}
                        </a>
                    @endcan

                    @can('create Applicants')
                        <button type="button" class="btn btn-main font-hold fw-bold" id="hrLaCreateBtn">
                            <i class="fas fa-plus me-1"></i>{{ __('recruitment.add_applicant') }}
                        </button>
                    @endcan

                    <input type="text" id="hrLaSearch" class="form-control hr-la-header__search"
                           placeholder="{{ __('recruitment.search_applicants') }}">

                    <div class="hr-la-header__filters">

                        <div class="hr-la-filter-field">
                            <select id="hrLaJobPostingFilter" class="form-select form-select-sm">
                                <option value="">{{ __('recruitment.job_posting') }}: {{ __('recruitment.all_job_postings') }}</option>
                                @foreach ($jobPostings as $jp)
                                    <option value="{{ $jp->id }}">{{ $jp->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="hr-la-filter-field">
                            <select id="hrLaStatusFilter" class="form-select form-select-sm">
                                <option value="">{{ __('recruitment.status') }}: {{ __('recruitment.all_status') }}</option>
                                <option value="pending">{{ __('recruitment.pending') }}</option>
                                <option value="reviewed">{{ __('hr::hr.reviewed') }}</option>
                                <option value="accepted">{{ __('recruitment.accepted') }}</option>
                                <option value="rejected">{{ __('recruitment.rejected') }}</option>
                                <option value="on_hold">{{ __('recruitment.on_hold') }}</option>
                            </select>
                        </div>

                        <div class="hr-la-filter-field hr-la-filter-field--date">
                            <div class="d-flex align-items-center gap-1">
                                <input type="date" id="hrLaDateFrom" class="form-control form-control-sm" style="width:130px">
                                <span class="text-muted fw-bold">—</span>
                                <input type="date" id="hrLaDateTo" class="form-control form-control-sm" style="width:130px">
                            </div>
                        </div>

                        <span id="hrLaFiltersCount" class="badge rounded-pill bg-success d-none"></span>

                        <button type="button" id="hrLaClearFilters" class="btn btn-sm btn-outline-danger d-none">
                            <i class="las la-sync-alt me-1"></i>{{ __('recruitment.reset_filters') }}
                        </button>

                    </div>
                </div>
            </div>

            <div class="card-body table-responsive">
                <div class="mb-2 font-hold fw-bold">
                    {{ __('recruitment.latest_applicants') }}
                    <span class="badge bg-primary" id="hrLaCount">0</span>
                    <small class="text-muted ms-2">({{ __('recruitment.latest_50') }})</small>
                </div>
                <table class="table table-striped mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th class="font-hold fw-bold text-start">{{ __('hr::hr.name') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.phone') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.job_posting') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.status') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.cv_file') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.created_at') }}</th>
                            @can('edit CVs')
                                <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody id="hrLaTableBody"></tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Modal إضافة متقدم --}}
    <div class="modal fade" id="hrLaCreateModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold">{{ __('recruitment.add_applicant') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrLaCreateForm" enctype="multipart/form-data">
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
                                <select name="cv_id" class="form-select font-hold fw-bold" id="hrLaCvSelect">
                                    <option value="">{{ __('recruitment.select_cv') }}</option>
                                </select>
                                <div class="invalid-feedback"></div>
                                <small class="text-muted">{{ __('recruitment.select_cv_hint') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary font-hold" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-main font-hold fw-bold" id="hrLaCreateSubmit">{{ __('hr::hr.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal تغيير الحالة --}}
    <div class="modal fade" id="hrLaStatusModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h6 class="modal-title font-hold fw-bold">{{ __('recruitment.change_status') }}</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrLaStatusForm">
                    <div class="modal-body">
                        <p class="font-hold fw-bold mb-2" id="hrLaStatusApplicantName"></p>
                        <select name="status" class="form-select font-hold fw-bold" id="hrLaStatusSelect">
                            <option value="pending">{{ __('recruitment.pending') }}</option>
                            <option value="reviewed">{{ __('hr::hr.reviewed') }}</option>
                            <option value="accepted">{{ __('recruitment.accepted') }}</option>
                            <option value="rejected">{{ __('recruitment.rejected') }}</option>
                            <option value="on_hold">{{ __('recruitment.on_hold') }}</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-main btn-sm font-hold fw-bold" id="hrLaStatusSubmit">{{ __('hr::hr.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    .hr-la-index .hr-la-header { background: var(--masar-card, #fff); }
    .hr-la-index .hr-la-header__row { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; }
    .hr-la-index .hr-la-header__search { flex: 1 1 220px; min-width: 180px; border-radius: .6rem; }
    .hr-la-index .hr-la-header__filters { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
    .hr-la-index .hr-la-filter-field { min-width: 160px; }
    .hr-la-index .hr-la-filter-field--date { min-width: auto; }
    @media (max-width: 991.98px) { .hr-la-index .btn { width: 100%; } }
</style>
@endpush

@push('scripts')
<script>
    window._hrLaConfig = {
        routes: {
            data:          @json(route('recruitment.latest-applicants.data')),
            status:        @json(route('recruitment.latest-applicants.status', ['cv' => '__ID__'])),
            download:      @json(route('recruitment.latest-applicants.download', ['cv' => '__ID__'])),
            cvShow:        @json(route('recruitment.cvs.show', ['cv' => '__ID__'])),
            jobPosting:    @json(route('recruitment.job-postings.show', ['job_posting' => '__ID__'])),
            store:         @json(route('recruitment.applicants.store')),
            cvSelectList:  @json(route('recruitment.cvs.select-list'))
        },
        canEdit:          @json(auth()->user()->can('edit CVs')),
        canCreateApplicant: @json(auth()->user()->can('create Applicants')),
        csrfToken: @json(csrf_token()),
        statusLabels:  {
            pending:  @json(__('recruitment.pending')),
            reviewed: @json(__('hr::hr.reviewed')),
            accepted: @json(__('recruitment.accepted')),
            rejected: @json(__('recruitment.rejected')),
            on_hold:  @json(__('recruitment.on_hold'))
        },
        statusColors: { pending: 'warning', reviewed: 'info', accepted: 'success', rejected: 'danger', on_hold: 'secondary' },
        trans: {
            empty:        @json(__('recruitment.no_applicants_found')),
            hasFile:      @json(__('hr::hr.cv_file')),
            noFile:       @json(__('hr::hr.no_file')),
            download:     @json(__('hr::hr.download_cv')),
            noPosting:    'بدون إعلان',
            saved:        @json(__('recruitment.applicant_updated_successfully')),
            changeStatus: @json(__('recruitment.change_status')),
            created:      @json(__('recruitment.applicant_created_successfully')),
            selectCv:     'اختر سيرة ذاتية',
            selectCvHint: 'سيتم نسخ الملف من السيرة الذاتية المختارة'
        }
    };
</script>
<script src="{{ asset('js/hr-latest-applicants.js') }}?v={{ @filemtime(public_path('js/hr-latest-applicants.js')) }}"></script>
@endpush
