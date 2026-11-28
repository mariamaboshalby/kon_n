@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.employee_evaluations'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.employee_evaluations')],
        ],
    ])

    <div class="employee-evaluations-management" style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrEvalAlert" class="alert d-none" role="alert"></div>

        {{-- Action Bar --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div class="d-flex flex-wrap gap-2">
                @can('create Employee Evaluations')
                    <button type="button" class="btn btn-main font-hold fw-bold" id="hrEvalCreateBtn">
                        <i class="las la-plus me-1"></i>{{ __('hr::hr.add_new_evaluation') }}
                    </button>
                @endcan
                <button type="button" class="btn btn-main" id="hrEvalFilterToggle">
                    <i class="las la-filter me-1"></i>{{ __('hr::hr.filters') }}
                </button>
            </div>
            <input type="text" id="hrEvalSearch" class="form-control w-auto font-hold"
                   style="min-width: 220px;" placeholder="{{ __('hr::hr.search') }}">
        </div>

        {{-- Filter Card (Collapsed by default) --}}
        <div id="hrEvalFilterCard" class="card mb-3" style="display: none;">
            <div class="card-body py-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label font-hold fw-bold" style="color: var(--main-color);">
                            {{ __('hr::hr.employee') }}
                        </label>
                        <select id="hrEvalEmployeeFilter" class="form-select form-select-sm font-hold js-searchable-filter">
                            <option value="">{{ __('hr::hr.all_employees') }}</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold" style="color: var(--main-color);">
                            {{ __('hr::hr.final_rating') }}
                        </label>
                        <select id="hrEvalRatingFilter" class="form-select form-select-sm font-hold">
                            <option value="">{{ __('hr::hr.all') }}</option>
                            <option value="{{ __('hr::hr.excellent') }}">{{ __('hr::hr.excellent') }}</option>
                            <option value="{{ __('hr::hr.very_good') }}">{{ __('hr::hr.very_good') }}</option>
                            <option value="{{ __('hr::hr.good') }}">{{ __('hr::hr.good') }}</option>
                            <option value="{{ __('hr::hr.acceptable') }}">{{ __('hr::hr.acceptable') }}</option>
                            <option value="{{ __('hr::hr.weak') }}">{{ __('hr::hr.weak') }}</option>
                            <option value="{{ __('hr::hr.very_weak') }}">{{ __('hr::hr.very_weak') }}</option>
                            <option value="{{ __('hr::hr.unacceptable') }}">{{ __('hr::hr.unacceptable') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold" style="color: var(--main-color);">
                            {{ __('hr::hr.date_from') }}
                        </label>
                        <input type="date" id="hrEvalDateFrom" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label font-hold fw-bold" style="color: var(--main-color);">
                            {{ __('hr::hr.date_to') }}
                        </label>
                        <input type="date" id="hrEvalDateTo" class="form-control form-control-sm">
                    </div>
                    <div class="col-auto d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="hrEvalClearBtn"
                                title="{{ __('hr::hr.reset_filter') }}">
                            <i class="las la-redo"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-bordered text-center mb-0">
                    <thead class="table-light align-middle">
                        <tr>
                            <th class="font-hold fw-bold">#</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.employee') }}</th>
                            <th class="font-hold fw-bold d-none d-md-table-cell">{{ __('hr::hr.job_title') }}</th>
                            <th class="font-hold fw-bold d-none d-md-table-cell">{{ __('hr::hr.department') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.evaluation_date') }}</th>
                            <th class="font-hold fw-bold d-none d-lg-table-cell">{{ __('hr::hr.direct_manager') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.total_score') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.final_rating') }}</th>
                            @canany(['view Employee Evaluations', 'edit Employee Evaluations', 'delete Employee Evaluations'])
                                <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody id="hrEvalTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Create / Edit Modal --}}
    <div class="modal fade" id="hrEvalModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold" id="hrEvalModalTitle">{{ __('hr::hr.add_new_evaluation') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="hrEvalForm">
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.employee') }} <span class="text-danger">*</span></label>
                                <select name="employee_id" id="hrEvalEmployeeSelect" class="form-select font-hold js-searchable-filter" required>
                                    <option value="">{{ __('hr::hr.select_employee') }}</option>
                                    @foreach ($employees as $emp)
                                        <option value="{{ $emp->id }}"
                                                data-job="{{ $emp->job?->title }}"
                                                data-department="{{ $emp->department?->title }}">
                                            {{ $emp->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.evaluation_date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="evaluation_date" id="hrEvalDate" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.job_title') }}</label>
                                <input type="text" id="hrEvalJobTitle" class="form-control" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.department') }}</label>
                                <input type="text" id="hrEvalDepartment" class="form-control" readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.direct_manager') }}</label>
                                <input type="text" name="direct_manager" class="form-control font-hold">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.evaluation_period_from') }} <span class="text-danger">*</span></label>
                                <input type="date" name="evaluation_period_from" id="hrEvalPeriodFrom" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.evaluation_period_to') }} <span class="text-danger">*</span></label>
                                <input type="date" name="evaluation_period_to" id="hrEvalPeriodTo" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('hr::hr.kpi_criterion') }}</th>
                                        <th>{{ __('hr::hr.description') }}</th>
                                        <th>{{ __('hr::hr.weight') }}</th>
                                        <th>{{ __('hr::hr.score') }} (0-100)</th>
                                        <th>{{ __('hr::hr.contribution') }}</th>
                                        <th>{{ __('hr::hr.notes') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="hrEvalKpiRows"></tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end font-hold fw-bold">{{ __('hr::hr.total_score') }}</td>
                                        <td class="font-hold fw-bold" id="hrEvalTotalScore">0</td>
                                        <td class="font-hold fw-bold" id="hrEvalFinalRating">-</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.close') }}</button>
                        <button type="submit" class="btn btn-main" id="hrEvalSubmitBtn">{{ __('hr::hr.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- View Modal --}}
    <div class="modal fade" id="hrEvalViewModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-main text-white">
                    <h5 class="modal-title font-hold fw-bold">{{ __('hr::hr.view_evaluation') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="hrEvalViewBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('hr::hr.close') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window._hrEmployeeEvaluationsConfig = {
        routes: {
            data:         @json(route('employee-evaluations.data')),
            store:        @json(route('employee-evaluations.store')),
            update:       @json(route('employee-evaluations.update',  ['evaluation' => '__ID__'])),
            destroy:      @json(route('employee-evaluations.destroy', ['evaluation' => '__ID__'])),
            show:         @json(route('employee-evaluations.show',    ['evaluation' => '__ID__'])),
            employeeKpis: @json(route('employee-evaluations.employee-kpis', ['employee' => '__ID__']))
        },
        canView:   @json(auth()->user()->can('view Employee Evaluations')),
        canEdit:   @json(auth()->user()->can('edit Employee Evaluations')),
        canDelete: @json(auth()->user()->can('delete Employee Evaluations')),
        csrfToken: @json(csrf_token()),
        ratings: {
            excellent:    @json(__('hr::hr.excellent')),
            veryGood:     @json(__('hr::hr.very_good')),
            good:         @json(__('hr::hr.good')),
            acceptable:   @json(__('hr::hr.acceptable')),
            weak:         @json(__('hr::hr.weak')),
            veryWeak:     @json(__('hr::hr.very_weak')),
            unacceptable: @json(__('hr::hr.unacceptable'))
        },
        trans: {
            add:              @json(__('hr::hr.add_new_evaluation')),
            editTitle:        @json(__('hr::hr.edit_evaluation')),
            save:             @json(__('hr::hr.save')),
            update:           @json(__('hr::hr.update')),
            empty:            @json(__('hr::hr.no_evaluations_found')),
            confirmDelete:    @json(__('hr::hr.confirm_delete_evaluation')),
            notSpecified:     @json(__('hr::hr.not_specified')),
            noNotes:          @json(__('hr::hr.no_notes')),
            employeeInfo:     @json(__('hr::hr.employee_information')),
            evaluationInfo:   @json(__('hr::hr.evaluation_information')),
            evaluationResults:@json(__('hr::hr.evaluation_results')),
            evaluationPeriod: @json(__('hr::hr.evaluation_period')),
            name:             @json(__('hr::hr.name')),
            jobTitle:         @json(__('hr::hr.job_title')),
            department:       @json(__('hr::hr.department')),
            evaluationDate:   @json(__('hr::hr.evaluation_date')),
            directManager:    @json(__('hr::hr.direct_manager')),
            totalScore:       @json(__('hr::hr.total_score')),
            finalRating:      @json(__('hr::hr.final_rating')),
            criterion:        @json(__('hr::hr.kpi_criterion')),
            description:      @json(__('hr::hr.description')),
            weight:           @json(__('hr::hr.weight')),
            score:            @json(__('hr::hr.score')),
            contribution:     @json(__('hr::hr.contribution')),
            notes:            @json(__('hr::hr.notes')),
            noKpis:           @json(__('hr::hr.no_kpis_for_employee')),
            view:             @json(__('hr::hr.view')),
            edit:             @json(__('hr::hr.edit')),
            delete:           @json(__('hr::hr.delete'))
        }
    };
</script>
<script src="{{ asset('js/hr-employee-evaluations.js') }}"></script>
<script>
    $('#hrEvalFilterToggle').on('click', function () {
        $('#hrEvalFilterCard').slideToggle(200);
    });
</script>
@endpush
