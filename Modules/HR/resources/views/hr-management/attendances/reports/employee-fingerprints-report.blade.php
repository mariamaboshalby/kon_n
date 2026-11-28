@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    <x-reports::report-page
        :title="__('hr.employee_fingerprints_report')"
        :breadcrumbs="[
            ['label' => __('general.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr.human_resources')],
            ['label' => __('hr.attendance')],
            ['label' => __('hr.employee_fingerprints_report')],
        ]"
        filter-collapse-id="hr-fingerprints-filters-collapse"
        print-only
        :with-card="false"
    >
        <div class="collapse mb-3 report-filters-collapse" id="hr-fingerprints-filters-collapse">
            <div class="card mb-0">
                <div class="card-body">
                    <form method="GET" action="{{ route('hr.attendance.reports.employee-fingerprints') }}" class="report-filters">
                        <div class="row g-2 align-items-start">
                            <div class="col-12 col-md-6 col-lg-3 report-filters-col">
                                <div class="mb-2">
                                    <label class="form-label report-filter-label mb-1" for="hr-fp-date-from">{{ __('hr.date_from') }}</label>
                                    <input type="date" name="date_from" id="hr-fp-date-from" value="{{ $dateFrom }}" class="form-control report-filter-control">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label report-filter-label mb-1" for="hr-fp-date-to">{{ __('hr.date_to') }}</label>
                                    <input type="date" name="date_to" id="hr-fp-date-to" value="{{ $dateTo }}" class="form-control report-filter-control">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3 report-filters-col">
                                <div class="mb-0">
                                    <label class="form-label report-filter-label mb-1" for="hr-fp-employee">{{ __('hr.employee') }}</label>
                                    <select name="employee_id" id="hr-fp-employee" class="form-select report-filter-control js-searchable-filter" required>
                                        <option value="">{{ __('hr.select_employee') }}</option>
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->id }}" @selected($employeeId == $emp->id)>{{ $emp->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3 report-filters-col">
                                <div class="d-flex flex-wrap gap-2 mt-1">
                                    <button type="submit" class="btn btn-sm btn-primaryyy">
                                        <i class="fas fa-filter me-1"></i>{{ __('hr.generate_report') }}
                                    </button>
                                    <a href="{{ route('hr.attendance.reports.employee-fingerprints') }}" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-redo me-1"></i>{{ __('reports::reports.reset') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if(!$employee)
            <div class="alert alert-light border text-center py-5 mb-0">
                <i class="las la-fingerprint fs-1 text-muted d-block mb-2"></i>
                <div class="fw-bold">{{ __('hr.employee_fingerprints_select_prompt') }}</div>
            </div>
        @else
            <div class="d-flex flex-wrap align-items-center gap-2 mb-4 px-3 py-2 rounded border"
                 style="background: var(--masar-card, #fff); border-color: var(--masar-border, #dee2e6) !important;">
                <i class="las la-user fs-3 text-primary"></i>
                <span class="fw-bold fs-5 font-hold" style="color: var(--masar-text, #212529);">{{ $employee->name }}</span>
                <span class="badge bg-secondary">{{ $dateFrom }} — {{ $dateTo }}</span>
                <span class="badge bg-primary">{{ $totalPunches }} {{ __('hr.fingerprints_count') }}</span>
            </div>

            @if($days->isEmpty())
                <div class="alert alert-warning text-center mb-0">
                    {{ __('hr.employee_fingerprints_empty') }}
                </div>
            @else
                <div class="hr-fp-report">
                    @foreach($days as $dayKey => $punches)
                        @php
                            $dayLabel = \Carbon\Carbon::parse($dayKey)->translatedFormat('l Y-m-d');
                        @endphp
                        <div class="hr-fp-day-row d-flex flex-wrap align-items-stretch gap-2 mb-3 pb-3 border-bottom">
                            <div class="hr-fp-day-label d-flex align-items-center flex-shrink-0 px-3 py-2 rounded border"
                                 style="min-width: 160px; background: var(--masar-card, #fff); border-color: var(--masar-border, #dee2e6) !important;">
                                <div>
                                    <div class="fw-bold font-hold" style="color: var(--masar-text, #212529);">{{ $dayLabel }}</div>
                                    <small class="text-muted">{{ $punches->count() }} {{ __('hr.fingerprints_count') }}</small>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2 flex-grow-1">
                                @foreach($punches as $punch)
                                    @php
                                        $isCheckIn = $punch->type === 'check_in';
                                        $typeColor = $isCheckIn ? 'success' : 'danger';
                                        $statusColor = match ($punch->status) {
                                            'approved' => 'success',
                                            'rejected' => 'danger',
                                            default => 'warning',
                                        };
                                        $statusLabel = match ($punch->status) {
                                            'approved' => __('hr.approved'),
                                            'rejected' => __('hr.rejected'),
                                            default => __('hr.pending'),
                                        };
                                        $timeDisplay = $punch->time
                                            ? \Carbon\Carbon::parse($punch->time)->format('h:i A')
                                            : '—';
                                    @endphp
                                    <div class="card border-0 shadow-sm hr-fp-punch-card border-bottom border-3 border-{{ $typeColor }}"
                                         style="min-width: 140px; max-width: 180px;">
                                        <div class="card-body p-2">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <span class="badge bg-{{ $typeColor }}">
                                                    {{ $isCheckIn ? __('hr.check_in') : __('hr.check_out') }}
                                                </span>
                                                <span class="badge bg-{{ $statusColor }}">
                                                    {{ $statusLabel }}
                                                </span>
                                            </div>
                                            <div class="fw-bold fs-5 font-hold" style="color: var(--masar-text, #212529);">
                                                {{ $timeDisplay }}
                                            </div>
                                            @if($punch->employee_attendance_finger_print_name)
                                                <div class="small text-muted text-truncate" title="{{ $punch->employee_attendance_finger_print_name }}">
                                                    <i class="las la-fingerprint"></i>
                                                    {{ $punch->employee_attendance_finger_print_name }}
                                                </div>
                                            @endif
                                            @if($punch->device_type)
                                                <div class="small text-muted text-truncate" title="{{ $punch->device_type }}">
                                                    <i class="las la-mobile"></i>
                                                    {{ $punch->device_type }}
                                                </div>
                                            @endif
                                            @if($punch->project_code)
                                                <div class="small text-muted text-truncate" title="{{ $punch->project_code }}">
                                                    <i class="las la-project-diagram"></i>
                                                    {{ $punch->project_code }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </x-reports::report-page>
@endsection

@push('styles')
<style>
    @media print {
        .hr-fp-day-row { break-inside: avoid; page-break-inside: avoid; }
        .hr-fp-punch-card { box-shadow: none !important; border: 1px solid #ccc !important; }
    }
</style>
@endpush
