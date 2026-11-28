@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('recruitment.interviews'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('recruitment.recruitment_management')],
            ['label' => __('recruitment.interviews')],
        ],
    ])

    <div style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrInterviewAlert" class="alert d-none" role="alert"></div>
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0 font-hold fw-bold">{{ __('recruitment.interviews') }}</h6>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-main font-hold fw-bold" id="hrInterviewFiltersToggle" data-target="#hrInterviewFiltersPanel" aria-expanded="false">
                        <i class="las la-filter me-1"></i>{{ __('recruitment.filters') }}
                    </button>
                    @can('create Interviews')
                        <button type="button" class="btn btn-main font-hold fw-bold" id="hrInterviewCreateBtn">
                            <i class="fas fa-plus me-2"></i>{{ __('recruitment.create_interview') }}
                        </button>
                    @endcan
                </div>
            </div>
            <div class="card-body border-bottom pb-2" id="hrInterviewFiltersPanel" style="display:none;">
                <div class="row g-2 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label font-hold fw-bold mb-1">{{ __('recruitment.search_interviews') }}</label>
                        <input type="text" id="hrInterviewSearch" class="form-control font-hold" placeholder="{{ __('recruitment.search_interviews') }}">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label font-hold fw-bold mb-1">{{ __('recruitment.status') }}</label>
                        <select id="hrInterviewStatusFilter" class="form-select font-hold">
                            <option value="">{{ __('recruitment.all_status') }}</option>
                            <option value="scheduled">{{ __('recruitment.scheduled') }}</option>
                            <option value="completed">{{ __('recruitment.completed') }}</option>
                            <option value="cancelled">{{ __('recruitment.cancelled') }}</option>
                            <option value="rescheduled">{{ __('recruitment.rescheduled') }}</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label font-hold fw-bold mb-1">{{ __('recruitment.interview_type') }}</label>
                        <select id="hrInterviewTypeFilter" class="form-select font-hold">
                            <option value="">{{ __('recruitment.all_types') }}</option>
                            <option value="phone">{{ __('recruitment.phone') }}</option>
                            <option value="video">{{ __('recruitment.video') }}</option>
                            <option value="in_person">{{ __('recruitment.in_person') }}</option>
                            <option value="panel">{{ __('recruitment.panel') }}</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label font-hold fw-bold mb-1">{{ __('recruitment.result') }}</label>
                        <select id="hrInterviewResultFilter" class="form-select font-hold">
                            <option value="">{{ __('recruitment.all_results') }}</option>
                            <option value="pending">{{ __('recruitment.pending') }}</option>
                            <option value="accepted">{{ __('recruitment.accepted') }}</option>
                            <option value="rejected">{{ __('recruitment.rejected') }}</option>
                            <option value="on_hold">{{ __('recruitment.on_hold') }}</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label font-hold fw-bold mb-1">{{ __('recruitment.job_posting') }}</label>
                        <select id="hrInterviewJobFilter" class="form-select font-hold">
                            <option value="">{{ __('recruitment.all_job_postings') }}</option>
                            @foreach ($jobPostings as $jp)
                                <option value="{{ $jp->id }}">{{ $jp->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label font-hold fw-bold mb-1">{{ __('recruitment.interviewer') }}</label>
                        <select id="hrInterviewInterviewerFilter" class="form-select font-hold">
                            <option value="">{{ __('recruitment.all_interviewers') }}</option>
                            @foreach ($interviewers as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label font-hold fw-bold mb-1">{{ __('recruitment.from') }} ({{ __('recruitment.scheduled_at') }})</label>
                        <input type="date" id="hrInterviewDateFrom" class="form-control font-hold">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label font-hold fw-bold mb-1">{{ __('recruitment.to') }} ({{ __('recruitment.scheduled_at') }})</label>
                        <input type="date" id="hrInterviewDateTo" class="form-control font-hold">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <button type="button" id="hrInterviewResetFilters" class="btn btn-outline-secondary font-hold fw-bold w-100">
                            <i class="fas fa-rotate-left me-1"></i>{{ __('recruitment.reset_filters') }}
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive">
                <div class="mb-2 font-hold fw-bold">{{ __('recruitment.interviews_list') }} <span class="badge bg-primary" id="hrInterviewCount">0</span></div>
                <table class="table table-striped mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th class="font-hold fw-bold">{{ __('recruitment.cv') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.job_posting') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.interview_type') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.status') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.scheduled_at') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.interviewer') }}</th>
                            <th class="font-hold fw-bold">{{ __('recruitment.result') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody id="hrInterviewTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrInterviewModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrInterviewModalTitle">{{ __('recruitment.create_interview') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrInterviewForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.cv') }} <span class="text-danger">*</span></label>
                                <select name="cv_id" class="form-select font-hold fw-bold" required>
                                    <option value="">{{ __('recruitment.select_cv') }}</option>
                                    @foreach ($cvs as $cv)
                                        <option value="{{ $cv->id }}">{{ $cv->name }}</option>
                                    @endforeach
                                </select>
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
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.interview_type') }} <span class="text-danger">*</span></label>
                                <select name="interview_type" class="form-select font-hold fw-bold" required>
                                    <option value="in_person">{{ __('recruitment.in_person') }}</option>
                                    <option value="phone">{{ __('recruitment.phone') }}</option>
                                    <option value="video">{{ __('recruitment.video') }}</option>
                                    <option value="panel">{{ __('recruitment.panel') }}</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.status') }} <span class="text-danger">*</span></label>
                                <select name="status" class="form-select font-hold fw-bold" required>
                                    <option value="scheduled">{{ __('recruitment.scheduled') }}</option>
                                    <option value="completed">{{ __('recruitment.completed') }}</option>
                                    <option value="cancelled">{{ __('recruitment.cancelled') }}</option>
                                    <option value="rescheduled">{{ __('recruitment.rescheduled') }}</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.scheduled_at') }} <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="scheduled_at" class="form-control font-hold fw-bold" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.duration') }}</label>
                                <input type="text" name="duration" class="form-control font-hold fw-bold" inputmode="numeric" value="60">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.location') }}</label>
                                <input type="text" name="location" class="form-control font-hold fw-bold">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.interviewer') }}</label>
                                <select name="interviewer_id" class="form-select font-hold fw-bold">
                                    <option value="">{{ __('recruitment.select_interviewer') }}</option>
                                    @foreach ($interviewers as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.result') }}</label>
                                <select name="result" class="form-select font-hold fw-bold">
                                    <option value="">—</option>
                                    <option value="pending">{{ __('recruitment.pending') }}</option>
                                    <option value="accepted">{{ __('recruitment.accepted') }}</option>
                                    <option value="rejected">{{ __('recruitment.rejected') }}</option>
                                    <option value="on_hold">{{ __('recruitment.on_hold') }}</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.notes') }}</label>
                                <textarea name="notes" class="form-control font-hold fw-bold" rows="2"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('recruitment.feedback') }}</label>
                                <textarea name="feedback" class="form-control font-hold fw-bold" rows="2"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-main" id="hrInterviewSubmitBtn">{{ __('hr::hr.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrInterviewViewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold">{{ __('recruitment.interview_details') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="hrInterviewViewBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.close') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window._hrInterviewsConfig = {
        routes: {
            data: @json(route('recruitment.interviews.data')),
            store: @json(route('recruitment.interviews.store')),
            update: @json(route('recruitment.interviews.update', ['interview' => '__ID__'])),
            destroy: @json(route('recruitment.interviews.destroy', ['interview' => '__ID__']))
        },
        columnCount: 8,
        canEdit: @json(auth()->user()->can('edit Interviews')),
        canDelete: @json(auth()->user()->can('delete Interviews')),
        csrfToken: @json(csrf_token()),
        typeLabels: {
            phone: @json(__('recruitment.phone')),
            video: @json(__('recruitment.video')),
            in_person: @json(__('recruitment.in_person')),
            panel: @json(__('recruitment.panel'))
        },
        statusLabels: {
            scheduled: @json(__('recruitment.scheduled')),
            completed: @json(__('recruitment.completed')),
            cancelled: @json(__('recruitment.cancelled')),
            rescheduled: @json(__('recruitment.rescheduled'))
        },
        resultLabels: {
            pending: @json(__('recruitment.pending')),
            accepted: @json(__('recruitment.accepted')),
            rejected: @json(__('recruitment.rejected')),
            on_hold: @json(__('recruitment.on_hold'))
        },
        trans: {
            add: @json(__('recruitment.create_interview')),
            editTitle: @json(__('recruitment.edit_interview')),
            save: @json(__('hr::hr.save')),
            update: @json(__('hr::hr.update')),
            empty: @json(__('recruitment.no_interviews_found')),
            confirmDelete: @json(__('recruitment.confirm_delete_interview')),
            saved: @json(__('recruitment.interview_created_successfully')),
            deleted: @json(__('recruitment.interview_deleted_successfully')),
            cv: @json(__('recruitment.cv')),
            jobPosting: @json(__('recruitment.job_posting')),
            type: @json(__('recruitment.interview_type')),
            status: @json(__('recruitment.status')),
            scheduled: @json(__('recruitment.scheduled_at')),
            duration: @json(__('recruitment.duration')),
            location: @json(__('recruitment.location')),
            interviewer: @json(__('recruitment.interviewer')),
            result: @json(__('recruitment.result')),
            notes: @json(__('recruitment.notes')),
            feedback: @json(__('recruitment.feedback')),
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
<script src="{{ asset('js/hr-interviews.js') }}?v={{ filemtime(public_path('js/hr-interviews.js')) }}"></script>
@endpush
