@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('recruitment.interview_calendar'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('recruitment.recruitment_management')],
            ['label' => __('recruitment.interviews'), 'url' => route('recruitment.interviews.index')],
            ['label' => __('recruitment.interview_calendar')],
        ],
    ])

    <div style="font-family: var(--masar-font-family); direction: rtl;">
        <div class="card mb-3">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="mb-0 font-hold fw-bold">{{ __('recruitment.interview_calendar') }}</h4>
                    <p class="text-muted mb-0" id="hrCalMonthLabel"></p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-main" id="hrCalFilterToggle">
                        <i class="las la-filter me-1"></i>{{ __('recruitment.filters') }}
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="hrCalPrev">{{ __('recruitment.previous_month') }}</button>
                    <button type="button" class="btn btn-outline-primary" id="hrCalToday">{{ __('recruitment.today') }}</button>
                    <button type="button" class="btn btn-outline-secondary" id="hrCalNext">{{ __('recruitment.next_month') }}</button>
                </div>
            </div>
        </div>

        {{-- Filter Card (Collapsed by default) --}}
        <div id="hrCalFilterCard" class="card mb-3" style="display: none;">
            <div class="card-body py-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label font-hold fw-bold" style="color: var(--main-color);">{{ __('recruitment.interview_type') }}</label>
                        <select id="hrCalTypeFilter" class="form-select form-select-sm font-hold">
                            <option value="">{{ __('recruitment.all_types') }}</option>
                            <option value="phone">{{ __('recruitment.phone') }}</option>
                            <option value="video">{{ __('recruitment.video') }}</option>
                            <option value="in_person">{{ __('recruitment.in_person') }}</option>
                            <option value="panel">{{ __('recruitment.panel') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label font-hold fw-bold" style="color: var(--main-color);">{{ __('recruitment.status') }}</label>
                        <select id="hrCalStatusFilter" class="form-select form-select-sm font-hold">
                            <option value="">{{ __('recruitment.all_status') }}</option>
                            <option value="scheduled">{{ __('recruitment.scheduled') }}</option>
                            <option value="completed">{{ __('recruitment.completed') }}</option>
                            <option value="cancelled">{{ __('recruitment.cancelled') }}</option>
                            <option value="rescheduled">{{ __('recruitment.rescheduled') }}</option>
                        </select>
                    </div>
                    <div class="col-auto d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="hrCalClearBtn"
                                title="{{ __('recruitment.reset_filters') }}">
                            <i class="las la-redo"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th class="text-center">{{ __('recruitment.sunday') }}</th>
                            <th class="text-center">{{ __('recruitment.monday') }}</th>
                            <th class="text-center">{{ __('recruitment.tuesday') }}</th>
                            <th class="text-center">{{ __('recruitment.wednesday') }}</th>
                            <th class="text-center">{{ __('recruitment.thursday') }}</th>
                            <th class="text-center">{{ __('recruitment.friday') }}</th>
                            <th class="text-center">{{ __('recruitment.saturday') }}</th>
                        </tr>
                    </thead>
                    <tbody id="hrCalBody"></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window._hrInterviewCalendarConfig = {
        routes: { data: @json(route('recruitment.interviews.calendar.data')) },
        initialMonth: @json(now()->format('Y-m')),
        trans: {
            today:   @json(__('recruitment.today')),
            unknown: @json(__('recruitment.unknown'))
        }
    };
</script>
<script src="{{ asset('js/hr-interview-calendar.js') }}"></script>
<script>
    $('#hrCalFilterToggle').on('click', function () {
        $('#hrCalFilterCard').slideToggle(200);
    });
</script>
@endpush
