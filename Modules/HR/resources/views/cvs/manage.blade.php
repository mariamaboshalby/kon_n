@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('recruitment.cv_management'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('recruitment.recruitment_management')],
            ['label' => __('recruitment.cvs')],
        ],
    ])

    <div style="font-family: var(--masar-font-family); direction: rtl;" class="hr-cv-index">
        <div id="hrCvAlert" class="alert d-none" role="alert"></div>
        <div class="card mb-3">
            <div class="card-header hr-cv-header">
                <div class="hr-cv-header__row">
                    @can('create CVs')
                        <button type="button" class="btn btn-main font-hold fw-bold hr-cv-header__add" id="hrCvCreateBtn">
                            <i class="fas fa-plus me-2"></i>{{ __('hr::hr.create_cv') }}
                        </button>
                    @else
                        <span></span>
                    @endcan

                    <input type="text" id="hrCvSearch" class="form-control hr-cv-header__search"
                           placeholder="{{ __('hr::hr.search_cvs') }}" aria-label="{{ __('hr::hr.search_cvs') }}">

                    <div class="hr-cv-header__filters">
                        <div class="hr-cv-filter-field">
                            <select id="hrCvGenderFilter" class="form-select form-select-sm hr-cv-filter-select"
                                    aria-label="{{ __('hr::hr.gender') }}">
                                <option value="">{{ __('hr::hr.gender') }}: {{ __('hr::hr.all') }}</option>
                                <option value="male">{{ __('recruitment.male') }}</option>
                                <option value="female">{{ __('recruitment.female') }}</option>
                                <option value="other">{{ __('general.other') }}</option>
                            </select>
                        </div>

                        <div class="hr-cv-filter-field">
                            <select id="hrCvMaritalFilter" class="form-select form-select-sm hr-cv-filter-select"
                                    aria-label="{{ __('hr::hr.marital_status') }}">
                                <option value="">{{ __('hr::hr.marital_status') }}: {{ __('hr::hr.all') }}</option>
                                <option value="single">{{ __('recruitment.single') }}</option>
                                <option value="married">{{ __('recruitment.married') }}</option>
                                <option value="divorced">{{ __('recruitment.divorced') }}</option>
                                <option value="widowed">{{ __('recruitment.widowed') }}</option>
                            </select>
                        </div>

                        <div class="hr-cv-filter-field">
                            <input type="text" id="hrCvNationalityFilter" class="form-control form-control-sm hr-cv-filter-select"
                                   placeholder="{{ __('hr::hr.nationality') }}" aria-label="{{ __('hr::hr.nationality') }}">
                        </div>

                        <div class="hr-cv-filter-field">
                            <select id="hrCvStatusFilter" class="form-select form-select-sm hr-cv-filter-select"
                                    aria-label="{{ __('recruitment.status') }}">
                                <option value="">{{ __('recruitment.status') }}: {{ __('recruitment.all_status') }}</option>
                                <option value="pending">{{ __('recruitment.pending') }}</option>
                                <option value="reviewed">{{ __('hr::hr.reviewed') }}</option>
                                <option value="accepted">{{ __('recruitment.accepted') }}</option>
                                <option value="rejected">{{ __('recruitment.rejected') }}</option>
                                <option value="on_hold">{{ __('recruitment.on_hold') }}</option>
                            </select>
                        </div>

                        <div class="hr-cv-filter-field">
                            <select id="hrCvJobPostingFilter" class="form-select form-select-sm hr-cv-filter-select"
                                    aria-label="{{ __('recruitment.job_posting') }}">
                                <option value="">{{ __('recruitment.job_posting') }}: {{ __('recruitment.all_job_postings') }}</option>
                                @foreach ($jobPostings as $jp)
                                    <option value="{{ $jp->id }}">{{ $jp->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="hr-cv-filter-field hr-cv-filter-field--date">
                            <div class="d-flex align-items-center gap-1">
                                <input type="date" id="hrCvDateFrom" class="form-control form-control-sm hr-cv-filter-date"
                                       aria-label="{{ __('hr::hr.from_date') }}">
                                <span class="hr-cv-filter-date__sep">—</span>
                                <input type="date" id="hrCvDateTo" class="form-control form-control-sm hr-cv-filter-date"
                                       aria-label="{{ __('hr::hr.to_date') }}">
                            </div>
                        </div>

                        <span id="hrCvFiltersCount" class="hr-cv-filter-count d-none"></span>

                        <button type="button" id="hrCvClearFilters" class="btn btn-sm hr-cv-filter-clear d-none">
                            <i class="las la-sync-alt me-1"></i>{{ __('hr::hr.clear_filters') }}
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive">
                <div class="mb-2 font-hold fw-bold">{{ __('hr::hr.cvs_list') }} <span class="badge bg-primary" id="hrCvCount">0</span></div>
                <table class="table table-striped mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th class="font-hold fw-bold">{{ __('hr::hr.name') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.job_title') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.status') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.phone') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.email') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.gender') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.nationality') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.cv_file') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody id="hrCvTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrCvModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrCvModalTitle">{{ __('hr::hr.create_cv') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrCvForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <h6 class="font-hold fw-bold text-main mb-3">{{ __('hr::hr.personal_information') }}</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.full_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control font-hold fw-bold" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.email') }}</label>
                                <input type="email" name="email" class="form-control font-hold fw-bold">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.phone') }} <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control font-hold fw-bold" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.birth_date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="birth_date" class="form-control font-hold fw-bold" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.gender') }} <span class="text-danger">*</span></label>
                                <select name="gender" class="form-select font-hold fw-bold" required>
                                    <option value="male">{{ __('recruitment.male') }}</option>
                                    <option value="female">{{ __('recruitment.female') }}</option>
                                    <option value="other">{{ __('general.other') }}</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.marital_status') }} <span class="text-danger">*</span></label>
                                <select name="marital_status" class="form-select font-hold fw-bold" required>
                                    <option value="single">{{ __('recruitment.single') }}</option>
                                    <option value="married">{{ __('recruitment.married') }}</option>
                                    <option value="divorced">{{ __('recruitment.divorced') }}</option>
                                    <option value="widowed">{{ __('recruitment.widowed') }}</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.nationality') }} <span class="text-danger">*</span></label>
                                <input type="text" name="nationality" class="form-control font-hold fw-bold" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.religion') }} <span class="text-danger">*</span></label>
                                <input type="text" name="religion" class="form-control font-hold fw-bold" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.country') }}</label>
                                <input type="text" name="country" class="form-control font-hold fw-bold">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.state') }}</label>
                                <input type="text" name="state" class="form-control font-hold fw-bold">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.city') }}</label>
                                <input type="text" name="city" class="form-control font-hold fw-bold">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('common.address') }}</label>
                                <input type="text" name="address" class="form-control font-hold fw-bold">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <h6 class="font-hold fw-bold text-main mb-3 mt-2">{{ __('hr::hr.professional_information') }}</h6>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.professional_summary') }}</label>
                                <textarea name="summary" class="form-control font-hold fw-bold" rows="2"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.skills') }}</label>
                                <textarea name="skills" class="form-control font-hold fw-bold" rows="2"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.work_experience') }}</label>
                                <textarea name="experience" class="form-control font-hold fw-bold" rows="2"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.education') }}</label>
                                <textarea name="education" class="form-control font-hold fw-bold" rows="2"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.projects') }}</label>
                                <textarea name="projects" class="form-control font-hold fw-bold" rows="2"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.certifications') }}</label>
                                <textarea name="certifications" class="form-control font-hold fw-bold" rows="2"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.languages') }}</label>
                                <textarea name="languages" class="form-control font-hold fw-bold" rows="2"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.interests') }}</label>
                                <textarea name="interests" class="form-control font-hold fw-bold" rows="2"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.references') }}</label>
                                <textarea name="references" class="form-control font-hold fw-bold" rows="2"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.cover_letter') }}</label>
                                <textarea name="cover_letter" class="form-control font-hold fw-bold" rows="2"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.portfolio_url') }}</label>
                                <input type="text" name="portfolio" class="form-control font-hold fw-bold">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.job_title') }}</label>
                                <input type="text" name="job_title" class="form-control font-hold fw-bold"
                                       placeholder="{{ __('recruitment.job_title') }}">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.job_posting') }}</label>
                                <select name="job_posting_id" class="form-select font-hold fw-bold">
                                    <option value="">{{ __('recruitment.select_job_posting') }}</option>
                                    @foreach ($jobPostings as $jp)
                                        <option value="{{ $jp->id }}">{{ $jp->title }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
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
                            <div class="col-md-4 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.cv_file') }}</label>
                                <input type="file" name="cv_file" class="form-control font-hold fw-bold" accept=".pdf,.doc,.docx">
                                <div class="invalid-feedback"></div>
                                <small id="hrCvCurrentFileHint" class="text-muted d-none"></small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-main" id="hrCvSubmitBtn">{{ __('hr::hr.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrCvViewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold">{{ __('hr::hr.cv_details') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="hrCvViewBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.close') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    /* CV form modal scrolling — the <form> wrapper breaks Bootstrap's
       modal-dialog-scrollable chain, so enforce flex scroll manually */
    #hrCvModal .modal-dialog {
        max-height: calc(100vh - 30px);
        display: flex;
        flex-direction: column;
    }

    #hrCvModal .modal-content {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
        max-height: calc(100vh - 30px);
    }

    #hrCvModal #hrCvForm {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
    }

    #hrCvModal .modal-header,
    #hrCvModal .modal-footer {
        flex: 0 0 auto;
    }

    #hrCvModal .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
    }

    /* Inline filter row — matches the HR employees index header */
    .hr-cv-index .hr-cv-header {
        background: var(--masar-card, #fff);
    }

    .hr-cv-index .hr-cv-header__row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .75rem;
    }

    .hr-cv-index .hr-cv-header__search {
        flex: 1 1 240px;
        min-width: 200px;
        border-radius: .6rem;
    }

    .hr-cv-index .hr-cv-header__filters {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
    }

    .hr-cv-index .hr-cv-filter-field {
        display: flex;
        flex-direction: column;
        gap: .25rem;
        min-width: 140px;
    }

    .hr-cv-index .hr-cv-filter-field--date {
        min-width: auto;
    }

    .hr-cv-index .hr-cv-filter-select {
        border-radius: .6rem;
        background-color: var(--masar-card, #fff);
        white-space: nowrap;
    }

    .hr-cv-index .hr-cv-filter-date {
        border-radius: .6rem;
        background-color: var(--masar-card, #fff);
        min-width: 0;
        width: 130px;
    }

    .hr-cv-index .hr-cv-filter-date__sep {
        color: var(--masar-text-muted, #6c757d);
        font-weight: 600;
        flex-shrink: 0;
    }

    .hr-cv-index .hr-cv-filter-count {
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

    .hr-cv-index .hr-cv-filter-clear {
        border: 1px solid color-mix(in srgb, var(--masar-swal-danger, #ef4444) 45%, transparent);
        color: var(--masar-swal-danger, #ef4444);
        background: transparent;
        border-radius: .6rem;
        font-weight: 600;
        white-space: nowrap;
        transition: all .2s ease;
    }

    .hr-cv-index .hr-cv-filter-clear:hover {
        background: color-mix(in srgb, var(--masar-swal-danger, #ef4444) 10%, transparent);
        color: var(--masar-swal-danger, #ef4444);
    }

    @media (max-width: 991.98px) {
        .hr-cv-index .hr-cv-header__add {
            width: 100%;
        }
    }

    @media (max-width: 575.98px) {
        .hr-cv-index .hr-cv-header__filters {
            width: 100%;
        }

        .hr-cv-index .hr-cv-header__filters .hr-cv-filter-field {
            flex: 1 1 auto;
        }

        .hr-cv-index .hr-cv-header__filters .hr-cv-filter-field--date {
            width: 100%;
        }

        .hr-cv-index .hr-cv-filter-date {
            flex: 1 1 auto;
            width: auto;
        }

        .hr-cv-index .hr-cv-filter-clear {
            width: 100%;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    window._hrCvsConfig = {
        routes: {
            data: @json(route('recruitment.cvs.data')),
            store: @json(route('recruitment.cvs.store')),
            update: @json(route('recruitment.cvs.update', ['cv' => '__ID__'])),
            destroy: @json(route('recruitment.cvs.destroy', ['cv' => '__ID__'])),
            download: @json(route('recruitment.cvs.download', ['cv' => '__ID__']))
        },
        columnCount: 9,
        canEdit: @json(auth()->user()->can('edit CVs')),
        canDelete: @json(auth()->user()->can('delete CVs')),
        csrfToken: @json(csrf_token()),
        genderLabels: {
            male: @json(__('recruitment.male')),
            female: @json(__('recruitment.female')),
            other: @json(__('general.other'))
        },
        maritalLabels: {
            single: @json(__('recruitment.single')),
            married: @json(__('recruitment.married')),
            divorced: @json(__('recruitment.divorced')),
            widowed: @json(__('recruitment.widowed'))
        },
        statusLabels: {
            pending: @json(__('recruitment.pending')),
            reviewed: @json(__('hr::hr.reviewed')),
            accepted: @json(__('recruitment.accepted')),
            rejected: @json(__('recruitment.rejected')),
            on_hold: @json(__('recruitment.on_hold'))
        },
        statusColors: {
            pending: 'warning',
            reviewed: 'info',
            accepted: 'success',
            rejected: 'danger',
            on_hold: 'secondary'
        },
        trans: {
            add: @json(__('hr::hr.create_cv')),
            editTitle: @json(__('hr::hr.edit_cv')),
            save: @json(__('hr::hr.save')),
            update: @json(__('hr::hr.update')),
            empty: @json(__('hr::hr.no_cvs_found')),
            confirmDelete: @json(__('hr::hr.confirm_delete_cv')),
            saved: @json(__('recruitment.cv_created_successfully')),
            deleted: @json(__('recruitment.cv_deleted_successfully')),
            hasFile: @json(__('hr::hr.cv_file')),
            noFile: @json(__('hr::hr.no_file')),
            download: @json(__('hr::hr.download_cv')),
            currentFile: @json(__('hr::hr.current_file')),
            name: @json(__('hr::hr.name')),
            email: @json(__('hr::hr.email')),
            phone: @json(__('hr::hr.phone')),
            birthDate: @json(__('hr::hr.birth_date')),
            gender: @json(__('hr::hr.gender')),
            maritalStatus: @json(__('hr::hr.marital_status')),
            nationality: @json(__('hr::hr.nationality')),
            religion: @json(__('hr::hr.religion')),
            address: @json(__('common.address')),
            summary: @json(__('hr::hr.professional_summary')),
            skills: @json(__('hr::hr.skills')),
            experience: @json(__('hr::hr.work_experience')),
            education: @json(__('hr::hr.education')),
            portfolio: @json(__('hr::hr.portfolio')),
            status: @json(__('recruitment.status')),
            jobTitle: @json(__('recruitment.job_title')),
            jobPosting: @json(__('recruitment.job_posting'))
        }
    };
</script>
<script src="{{ asset('js/hr-cvs.js') }}?v={{ filemtime(public_path('js/hr-cvs.js')) }}"></script>
@endpush
