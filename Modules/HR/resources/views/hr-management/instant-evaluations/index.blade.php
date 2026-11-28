@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.instant_evaluations'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.instant_evaluations')],
        ],
    ])

    @php
        $dash = $dashboard ?? ['totals' => [], 'top_positive' => [], 'top_negative' => [], 'recent' => []];
        $totals = $dash['totals'] ?? [];
    @endphp

    <div class="instant-evaluations-management" style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrInstantEvalAlert" class="alert d-none" role="alert"></div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-muted font-hold small">{{ __('hr::hr.instant_evaluation_total_all') }}</div>
                        <div class="fs-3 fw-bold text-main">{{ (int) ($totals['all'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-muted font-hold small">{{ __('hr::hr.instant_evaluation_total_positive') }}</div>
                        <div class="fs-3 fw-bold text-success">{{ (int) ($totals['positive'] ?? 0) }}</div>
                        <div class="small text-muted">{{ __('hr::hr.instant_evaluation_sum_positive') }}: +{{ (int) ($totals['sum_positive'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-muted font-hold small">{{ __('hr::hr.instant_evaluation_total_negative') }}</div>
                        <div class="fs-3 fw-bold text-danger">{{ (int) ($totals['negative'] ?? 0) }}</div>
                        <div class="small text-muted">{{ __('hr::hr.instant_evaluation_sum_negative') }}: {{ (int) ($totals['sum_negative'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 d-flex align-items-center justify-content-center">
                @can('create Instant Evaluations')
                    <button type="button" class="btn btn-main font-hold fw-bold w-100" id="hrInstantEvalCreateBtn">
                        <i class="las la-plus me-1"></i>{{ __('hr::hr.add_instant_evaluation') }}
                    </button>
                @endcan
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-success text-white font-hold fw-bold">{{ __('hr::hr.instant_evaluation_top_positive') }}</div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-sm text-center mb-0">
                            <thead><tr><th>#</th><th>{{ __('hr::hr.employee') }}</th><th>{{ __('hr::hr.instant_evaluation_sum_positive') }}</th><th>{{ __('hr::hr.instant_evaluations') }}</th></tr></thead>
                            <tbody>
                                @forelse (($dash['top_positive'] ?? []) as $i => $row)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td class="font-hold">{{ $row['employee_name'] ?? '-' }}</td>
                                        <td class="text-success fw-bold">+{{ $row['score_sum'] ?? 0 }}</td>
                                        <td>{{ $row['evaluations_count'] ?? 0 }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-muted">{{ __('hr::hr.no_instant_evaluations_found') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-danger text-white font-hold fw-bold">{{ __('hr::hr.instant_evaluation_top_negative') }}</div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-sm text-center mb-0">
                            <thead><tr><th>#</th><th>{{ __('hr::hr.employee') }}</th><th>{{ __('hr::hr.instant_evaluation_sum_negative') }}</th><th>{{ __('hr::hr.instant_evaluations') }}</th></tr></thead>
                            <tbody>
                                @forelse (($dash['top_negative'] ?? []) as $i => $row)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td class="font-hold">{{ $row['employee_name'] ?? '-' }}</td>
                                        <td class="text-danger fw-bold">{{ $row['score_sum'] ?? 0 }}</td>
                                        <td>{{ $row['evaluations_count'] ?? 0 }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-muted">{{ __('hr::hr.no_instant_evaluations_found') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <input type="text" id="hrInstantEvalSearch" class="form-control font-hold" placeholder="{{ __('hr::hr.search') }}">
                    </div>
                    <div class="col-md-2">
                        <select id="hrInstantEvalEmployeeFilter" class="form-select font-hold js-searchable-filter">
                            <option value="">{{ __('hr::hr.employee') }}</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="hrInstantEvalPolarityFilter" class="form-select font-hold">
                            <option value="">{{ __('hr::hr.instant_evaluation_polarity') }}</option>
                            <option value="positive">{{ __('hr::hr.instant_evaluation_positive') }}</option>
                            <option value="negative">{{ __('hr::hr.instant_evaluation_negative') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" id="hrInstantEvalDateFrom" class="form-control form-control-sm"
                               title="{{ __('hr::hr.date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <input type="date" id="hrInstantEvalDateTo" class="form-control form-control-sm"
                               title="{{ __('hr::hr.date_to') }}">
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="hrInstantEvalClearBtn"
                                title="{{ __('hr::hr.reset_filter') }}">
                            <i class="las la-redo"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header font-hold fw-bold">{{ __('hr::hr.instant_evaluation_recent') }}</div>
            <div class="card-body table-responsive">
                <table class="table table-bordered text-center mb-0">
                    <thead class="table-light align-middle">
                        <tr>
                            <th class="font-hold fw-bold">#</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.employee') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.instant_evaluation_polarity') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.instant_evaluation_score') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.instant_evaluation_item') }}</th>
                            <th class="font-hold fw-bold d-none d-lg-table-cell">{{ __('hr::hr.instant_evaluation_note') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.instant_evaluation_evaluator') }}</th>
                            <th class="font-hold fw-bold">{{ __('hr::hr.instant_evaluation_at') }}</th>
                            @can('delete Instant Evaluations')
                                <th class="font-hold fw-bold">{{ __('hr::hr.actions') }}</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody id="hrInstantEvalTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    @can('create Instant Evaluations')
        <div class="modal fade" id="hrInstantEvalModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-main text-white">
                        <h5 class="modal-title font-hold fw-bold">{{ __('hr::hr.add_instant_evaluation') }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="hrInstantEvalForm">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.employee') }} <span class="text-danger">*</span></label>
                                <select name="employee_id" id="hrInstantEvalEmployeeSelect" class="form-select font-hold js-searchable-filter" required>
                                    <option value="">{{ __('hr::hr.select_employee') }}</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.instant_evaluation_polarity') }} <span class="text-danger">*</span></label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="polarity" id="hrIePolPositive" value="positive" checked>
                                    <label class="btn btn-outline-success font-hold" for="hrIePolPositive">{{ __('hr::hr.instant_evaluation_positive') }}</label>
                                    <input type="radio" class="btn-check" name="polarity" id="hrIePolNegative" value="negative">
                                    <label class="btn btn-outline-danger font-hold" for="hrIePolNegative">{{ __('hr::hr.instant_evaluation_negative') }}</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.instant_evaluation_score') }} <span class="text-danger">*</span></label>
                                <select name="score" id="hrInstantEvalScore" class="form-select font-hold" required>
                                    <option value="">{{ __('hr::hr.select_score') }}</option>
                                    @for ($i = 5; $i >= 1; $i--)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endfor
                                </select>
                                <div class="invalid-feedback"></div>
                                <div class="form-text font-hold" id="hrInstantEvalScoreHint"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.instant_evaluation_item') }} <span class="text-danger">*</span></label>
                                <select name="item_id" id="hrInstantEvalItemSelect" class="form-select font-hold" required>
                                    <option value="">{{ __('hr::hr.select_instant_evaluation_item') }}</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-hold fw-bold">{{ __('hr::hr.instant_evaluation_note') }}</label>
                                <textarea name="note" id="hrInstantEvalNote" class="form-control font-hold" rows="3" maxlength="1000"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary font-hold" data-bs-dismiss="modal">{{ __('hr::hr.cancel') }}</button>
                            <button type="submit" class="btn btn-main font-hold fw-bold" id="hrInstantEvalSubmitBtn">{{ __('hr::hr.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection

@push('scripts')
    <script>
        window._hrInstantEvaluationsConfig = {
            urls: {
                data: @json(route('instant-evaluations.data')),
                store: @json(route('instant-evaluations.store')),
                destroy: @json(url('instant-evaluations')),
                items: @json(url('instant-evaluations/items')),
            },
            csrfToken: @json(csrf_token()),
            canDelete: @json(auth()->user()->can('delete Instant Evaluations')),
            items: {
                positive: @json(($positiveItems ?? collect())->map(fn ($i) => ['id' => $i->id, 'name' => $i->name])->values()),
                negative: @json(($negativeItems ?? collect())->map(fn ($i) => ['id' => $i->id, 'name' => $i->name])->values()),
            },
            lang: {
                noData: @json(__('hr::hr.no_instant_evaluations_found')),
                confirmDelete: @json(__('hr::hr.confirm_delete_instant_evaluation')),
                delete: @json(__('hr::hr.delete')),
                positive: @json(__('hr::hr.instant_evaluation_positive')),
                negative: @json(__('hr::hr.instant_evaluation_negative')),
                selectItem: @json(__('hr::hr.select_instant_evaluation_item')),
                scoreHintPositive: @json(__('hr::hr.instant_evaluation_positive') . ' (+N)'),
                scoreHintNegative: @json(__('hr::hr.instant_evaluation_negative') . ' (−N)'),
            }
        };
    </script>
    <script src="{{ asset('js/hr-instant-evaluations.js') }}?v={{ @filemtime(public_path('js/hr-instant-evaluations.js')) }}"></script>
@endpush
