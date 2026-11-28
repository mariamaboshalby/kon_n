@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.shifts'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.shifts')],
        ],
    ])

    <div class="shifts-management" style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrShiftAlert" class="alert d-none" role="alert"></div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('shifts.weekly.index') }}" class="btn btn-main font-hold fw-bold">
                        <i class="las la-calendar-week me-2"></i>{{ __('hr::hr.weekly_shift_board') }}
                    </a>
                    @can('create Shifts')
                        <button type="button" class="btn btn-main font-hold fw-bold" id="hrShiftCreateBtn">
                            <i class="las la-plus me-2"></i>{{ __('hr::hr.add_shift') }}
                        </button>
                    @endcan
                </div>
                <input type="text" id="hrShiftSearch" class="form-control w-auto" style="min-width: 200px;"
                       placeholder="{{ __('hr::hr.search_by_title') }}">
            </div>
            <div class="card-body">
                <div class="table-responsive" style="overflow-x: auto;">
                    <table class="table table-striped text-center mb-0">
                        <thead class="table-light align-middle">
                            <tr>
                                <th class="font-hold fw-bold">{{ __('hr::hr.name') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.start_time') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.beginning_check_in') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.ending_check_in') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.allowed_late_minutes') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.end_time') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.beginning_check_out') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.ending_check_out') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.allowed_early_leave_minutes') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.shift_type') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.shift_days') }}</th>
                                <th class="font-hold fw-bold">{{ __('hr::hr.notes') }}</th>
                                @canany(['edit Shifts', 'delete Shifts'])
                                    <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                                @endcanany
                            </tr>
                        </thead>
                        <tbody id="hrShiftTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrShiftModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrShiftModalTitle">{{ __('hr::hr.add_shift') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrShiftForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.shift_name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control font-hold" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.start_time') }} <span class="text-danger">*</span></label>
                                <input type="time" name="start_time" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.end_time') }} <span class="text-danger">*</span></label>
                                <input type="time" name="end_time" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.beginning_check_in') }}</label>
                                <input type="time" name="beginning_check_in" class="form-control">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.ending_check_in') }}</label>
                                <input type="time" name="ending_check_in" class="form-control">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.allowed_late_minutes') }}</label>
                            <input type="text" name="allowed_late_minutes" class="form-control" value="0">
                            <div class="invalid-feedback"></div>
                            <small class="form-text text-muted">{{ __('hr::hr.allowed_late_minutes_help') }}</small>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.beginning_check_out') }}</label>
                                <input type="time" name="beginning_check_out" class="form-control">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.ending_check_out') }}</label>
                                <input type="time" name="ending_check_out" class="form-control">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.allowed_early_leave_minutes') }}</label>
                            <input type="text" name="allowed_early_leave_minutes" class="form-control" value="0">
                            <div class="invalid-feedback"></div>
                            <small class="form-text text-muted">{{ __('hr::hr.allowed_early_leave_minutes_help') }}</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.shift_type') }} <span class="text-danger">*</span></label>
                            <select name="shift_type" class="form-select" required>
                                <option value="">{{ __('hr::hr.select_shift_type') }}</option>
                                @foreach ($shiftTypes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.shift_days') }} <span class="text-danger">*</span></label>
                            <div class="d-flex flex-wrap gap-2" id="hrShiftDays">
                                @foreach ($weekDays as $key => $label)
                                    <div class="form-check me-3">
                                        <input class="form-check-input" type="checkbox" name="days[]" id="hr_day_{{ $key }}" value="{{ $key }}">
                                        <label class="form-check-label" for="hr_day_{{ $key }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                            <div class="invalid-feedback d-block" data-error-for="days"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-hold fw-bold">{{ __('hr::hr.notes') }}</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                        <button type="submit" class="btn btn-main" id="hrShiftSubmitBtn">{{ __('hr::hr.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window._hrShiftsConfig = {
        routes: {
            data: @json(route('shifts.data')),
            store: @json(route('shifts.store')),
            update: @json(route('shifts.update', ['shift' => '__ID__'])),
            destroy: @json(route('shifts.destroy', ['shift' => '__ID__']))
        },
        canEdit: @json(auth()->user()->can('edit Shifts')),
        canDelete: @json(auth()->user()->can('delete Shifts')),
        shiftTypes: @json($shiftTypes),
        weekDays: @json($weekDays),
        csrfToken: @json(csrf_token()),
        trans: {
            add: @json(__('hr::hr.add_shift')),
            editTitle: @json(__('hr::hr.edit_shift')),
            save: @json(__('hr::hr.save')),
            update: @json(__('hr::hr.update')),
            edit: @json(__('hr::hr.edit')),
            delete: @json(__('hr::hr.delete')),
            empty: @json(__('hr::hr.no_shifts_found')),
            confirmDelete: @json(__('hr::hr.confirm_delete_shift')),
            saved: @json(__('hr::hr.shift_created_successfully')),
            deleted: @json(__('hr::hr.shift_deleted_successfully'))
        }
    };
</script>
<script src="{{ asset('js/hr-shifts.js') }}?v={{ filemtime(public_path('js/hr-shifts.js')) }}"></script>
@endpush
