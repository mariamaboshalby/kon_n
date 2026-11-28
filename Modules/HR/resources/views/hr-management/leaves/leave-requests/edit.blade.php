@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.edit_leave_request_details'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.leave_requests'), 'url' => route('hr.leaves.requests.index')],
            ['label' => __('hr::hr.edit_leave_request_details')],
        ],
    ])

    <div class="hr-leave-requests-form" style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrLeaveRequestAlert" class="alert d-none" role="alert"></div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title font-hold fw-bold mb-0">{{ __('hr::hr.edit_leave_request_details') }}</h3>
                <a href="{{ route('hr.leaves.requests.show', $leaveRequest) }}" class="btn btn-secondary font-hold fw-bold">
                    <i class="fas fa-arrow-left"></i>
                    {{ __('hr::hr.back_to_details') }}
                </a>
            </div>
            <div class="card-body">
                <div class="alert alert-info font-hold mb-4">
                    <h6 class="fw-bold"><i class="fas fa-info-circle"></i> {{ __('hr::hr.current_request_information') }}</h6>
                    <div class="row">
                        <div class="col-md-3">
                            <strong>{{ __('hr::hr.employee') }}:</strong> {{ $leaveRequest->employee->name }}
                        </div>
                        <div class="col-md-3">
                            <strong>{{ __('hr::hr.leave_type') }}:</strong> {{ $leaveRequest->leaveType->name }}
                        </div>
                        <div class="col-md-3">
                            <strong>{{ __('hr::hr.leave_status') }}:</strong>
                            {{ __('hr::hr.'.$leaveRequest->status) }}
                        </div>
                        <div class="col-md-3">
                            <strong>{{ __('hr::hr.current_duration') }}:</strong>
                            {{ number_format((float) $leaveRequest->duration_days, 1) }} {{ __('hr::hr.days') }}
                        </div>
                    </div>
                </div>

                @include('hr::hr-management.leaves.leave-requests.partials.form', [
                    'leaveRequest' => $leaveRequest,
                    'startDate' => $leaveRequest->start_date->format('Y-m-d'),
                    'endDate' => $leaveRequest->end_date->format('Y-m-d'),
                    'submitLabel' => __('hr::hr.save_changes'),
                    'cancelUrl' => route('hr.leaves.requests.show', $leaveRequest),
                ])
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window._hrLeaveRequestsConfig = {
        page: 'form',
        routes: {
            store: '',
            update: @json(route('hr.leaves.requests.update', $leaveRequest)),
            preview: @json(route('hr.leaves.requests.preview'))
        },
        isEdit: true,
        csrfToken: @json(csrf_token()),
        trans: {
            saving: @json(__('hr::hr.saving')),
            saveError: @json(__('hr::hr.error_loading')),
            overlap: @json(__('hr::hr.overlap_with_attendance')),
            noOverlap: @json(__('hr::hr.no_overlap'))
        }
    };
</script>
<script src="{{ asset('js/hr-leave-requests.js') }}"></script>
@endpush
