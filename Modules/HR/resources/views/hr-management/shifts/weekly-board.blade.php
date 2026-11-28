@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.weekly_shift_board'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.shifts'), 'url' => route('shifts.index')],
            ['label' => __('hr::hr.weekly_shift_board')],
        ],
    ])

    <div class="hr-week-screen" id="hrWeekBoard">
        <div id="hrWeekAlert" class="alert d-none mb-2" role="alert"></div>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <p class="mb-0 text-muted small">{{ __('hr::hr.weekly_shift_board_hint') }}</p>
            <div class="d-flex flex-wrap gap-2">
                @can('edit Shifts')
                    <button type="button" class="btn btn-outline-primary font-hold fw-bold" id="hrWeekCopyPrevBtn">{{ __('hr::hr.weekly_shift_copy_previous') }}</button>
                    <button type="button" class="btn btn-main font-hold fw-bold" id="hrWeekCommitBtn" disabled>{{ __('hr::hr.weekly_shift_save') }}</button>
                @endcan
                <a href="{{ route('shifts.index') }}" class="btn btn-main font-hold fw-bold">{{ __('hr::hr.shifts') }}</a>
            </div>
        </div>
        <div class="hr-week-toolbar mb-2">
            <div class="hr-week-nav">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="hrWeekPrevBtn" title="{{ __('hr::hr.weekly_shift_prev_week') }}">&rsaquo; {{ __('hr::hr.weekly_shift_prev_week') }}</button>
                <span class="hr-week-range font-hold fw-bold" id="hrWeekRangeLabel"></span>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="hrWeekNextBtn" title="{{ __('hr::hr.weekly_shift_next_week') }}">{{ __('hr::hr.weekly_shift_next_week') }} &lsaquo;</button>
                <button type="button" class="btn btn-sm btn-main" id="hrWeekTodayBtn">{{ __('hr::hr.weekly_shift_this_week') }}</button>
            </div>
            <div class="hr-week-settings">
                <label class="font-hold fw-bold" for="hrWeekDayStartHour">{{ __('hr::hr.weekly_shift_day_start') }}</label>
                <select id="hrWeekDayStartHour" class="form-select form-select-sm" @cannot('edit Shifts') disabled @endcannot>
                    @for ($hour = 0; $hour <= 23; $hour++)
                        <option value="{{ $hour }}">{{ sprintf('%02d:00', $hour) }}</option>
                    @endfor
                </select>
                <label class="font-hold fw-bold" for="hrWeekDayEndHour">{{ __('hr::hr.weekly_shift_day_end') }}</label>
                <select id="hrWeekDayEndHour" class="form-select form-select-sm" @cannot('edit Shifts') disabled @endcannot></select>
                <label class="font-hold fw-bold" for="hrWeekStartDay">{{ __('hr::hr.weekly_shift_week_start_day') }}</label>
                <select id="hrWeekStartDay" class="form-select form-select-sm" @cannot('edit Shifts') disabled @endcannot>
                    @foreach ($weekDayLabels as $weekday => $label)
                        <option value="{{ $weekday }}">{{ $label }}</option>
                    @endforeach
                </select>
                @can('edit Shifts')
                    <button type="button" class="btn btn-sm btn-main font-hold" id="hrWeekSettingsSaveBtn" disabled>{{ __('hr::hr.weekly_shift_settings_save') }}</button>
                @endcan
            </div>
        </div>
        <div class="hr-week-layout">
            <aside class="card hr-week-pool">
                <div class="card-header font-hold fw-bold py-2">{{ __('hr::hr.weekly_shift_employees') }}</div>
                <div class="card-body p-2">
                    <input type="text" id="hrWeekEmployeeSearch" class="form-control form-control-sm mb-2"
                           placeholder="{{ __('hr::hr.weekly_shift_search_employee') }}">
                    <div class="hr-week-pool-list" id="hrWeekEmployeeList"></div>
                </div>
            </aside>
            <section class="hr-week-cal">
                <div class="hr-week-axis" id="hrWeekAxis"></div>
                <div class="hr-week-days" id="hrWeekDays"></div>
            </section>
        </div>
    </div>

    <div class="modal fade" id="hrWeekModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrWeekModalTitle">{{ __('hr::hr.weekly_shift_edit') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrWeekForm">
                    <div class="modal-body">
                        <input type="hidden" id="hrWeekAssignmentId" value="">
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold" for="hrWeekDay">{{ __('hr::hr.weekly_shift_day') }}</label>
                            <select id="hrWeekDay" class="form-select font-hold" required></select>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label font-hold fw-bold" for="hrWeekStart">{{ __('hr::hr.start_time') }}</label>
                                <select id="hrWeekStart" class="form-select" required></select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label font-hold fw-bold" for="hrWeekEnd">{{ __('hr::hr.end_time') }}</label>
                                <select id="hrWeekEnd" class="form-select" required></select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        @can('edit Shifts')
                            <button type="button" class="btn btn-outline-danger font-hold" id="hrWeekRemoveBtn">{{ __('hr::hr.weekly_shift_remove') }}</button>
                            <button type="submit" class="btn btn-main font-hold fw-bold" id="hrWeekModalSaveBtn">{{ __('hr::hr.weekly_shift_save') }}</button>
                        @endcan
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/hr-weekly-shifts.css') }}">
@endpush

@push('scripts')
<script>
    window._hrWeeklyShiftsConfig = {
        routes: {
            data: @json(route('shifts.weekly.data')),
            assign: @json(route('shifts.weekly.assign')),
            unassign: @json(route('shifts.weekly.unassign')),
            save: @json(route('shifts.weekly.save')),
            settings: @json(route('shifts.weekly.settings'))
        },
        canEdit: @json(auth()->user()->can('edit Shifts')),
        weekDays: @json($weekDayLabels),
        csrfToken: @json(csrf_token()),
        trans: {
            noEmployees: @json(__('hr::hr.weekly_shift_no_employees')),
            assigned: @json(__('hr::hr.weekly_shift_assigned')),
            removed: @json(__('hr::hr.weekly_shift_removed')),
            remove: @json(__('hr::hr.weekly_shift_remove')),
            saveFailed: @json(__('hr::hr.weekly_shift_save_failed')),
            saved: @json(__('hr::hr.weekly_shift_saved')),
            selectEmployee: @json(__('hr::hr.weekly_shift_select_employee')),
            maxTwo: @json(__('hr::hr.weekly_shift_max_two')),
            overlap: @json(__('hr::hr.weekly_shift_overlap')),
            invalidRange: @json(__('hr::hr.weekly_shift_invalid_range')),
            nextDay: @json(__('hr::hr.weekly_shift_next_day')),
            settingsSaved: @json(__('hr::hr.weekly_shift_settings_saved')),
            invalidWindow: @json(__('hr::hr.weekly_shift_invalid_window')),
            copyConfirm: @json(__('hr::hr.weekly_shift_copy_confirm')),
            copied: @json(__('hr::hr.weekly_shift_copied')),
            noPrevious: @json(__('hr::hr.weekly_shift_no_previous')),
            unsavedConfirm: @json(__('hr::hr.weekly_shift_unsaved_confirm'))
        }
    };
</script>
<script src="{{ asset('js/hr-weekly-shifts.js') }}"></script>
@endpush
