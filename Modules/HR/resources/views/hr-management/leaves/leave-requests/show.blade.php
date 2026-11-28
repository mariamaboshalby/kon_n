@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.leave_request_details'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.leave_requests'), 'url' => route('hr.leaves.requests.index')],
            ['label' => __('hr::hr.leave_request_details')],
        ],
    ])

    <div class="container-fluid py-4" style="font-family: var(--masar-font-family); direction: rtl;">
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if (session('message'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>{{ session('message') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        <div id="hrLeaveRequestAlert" class="alert d-none" role="alert"></div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1 fw-bold font-hold">{{ __('hr::hr.leave_request_details') }}</h2>
                <p class="text-muted mb-0">{{ __('hr::hr.leave_request_view_subtitle') }}</p>
            </div>
            <a href="{{ route('hr.leaves.requests.index') }}" class="btn btn-secondary font-hold fw-bold">
                <i class="fas fa-arrow-right me-2"></i>{{ __('hr::hr.back_to_list') }}
            </a>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header py-3">
                        <h5 class="card-title mb-0 fw-bold font-hold">
                            <i class="fas fa-info-circle me-2"></i>{{ __('hr::hr.request_information') }}
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded">
                                    <label class="text-muted small mb-1 d-block">{{ __('hr::hr.employee') }}</label>
                                    <p class="mb-0 fw-bold font-hold">{{ $leaveRequest->employee->name }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded">
                                    <label class="text-muted small mb-1 d-block">{{ __('hr::hr.leave_type') }}</label>
                                    <p class="mb-0 fw-bold font-hold">{{ $leaveRequest->leaveType->name }}</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded">
                                    <label class="text-muted small mb-1 d-block">{{ __('hr::hr.start_date') }}</label>
                                    <p class="mb-0 fw-bold font-hold">{{ $leaveRequest->start_date->format('Y-m-d') }}</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded">
                                    <label class="text-muted small mb-1 d-block">{{ __('hr::hr.end_date') }}</label>
                                    <p class="mb-0 fw-bold font-hold">{{ $leaveRequest->end_date->format('Y-m-d') }}</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded">
                                    <label class="text-muted small mb-1 d-block">{{ __('hr::hr.duration') }}</label>
                                    <p class="mb-0 fw-bold font-hold text-info">{{ number_format((float) $leaveRequest->duration_days, 1) }} {{ __('hr::hr.days') }}</p>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="p-3 bg-light rounded">
                                    <label class="text-muted small mb-1 d-block">{{ __('hr::hr.leave_reason') }}</label>
                                    <p class="mb-0 font-hold">{{ $leaveRequest->reason ?: __('hr::hr.not_specified') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header py-3">
                        <h5 class="card-title mb-0 fw-bold font-hold">
                            <i class="fas fa-tasks me-2"></i>{{ __('hr::hr.request_status') }}
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <span class="badge fs-4 px-4 py-2 {{ $statusBadge }}">{{ $statusText }}</span>
                            <p class="text-muted mt-3 mb-0 font-hold">{{ $statusDescription }}</p>
                        </div>

                        @if ($leaveRequest->approver && $leaveRequest->approved_at)
                            <div class="alert alert-success border-0 mb-3">
                                <strong>{{ __('hr::hr.approved_by') }}:</strong>
                                <p class="mb-1 font-hold">{{ $leaveRequest->approver->name }}</p>
                                <small class="text-muted">{{ $leaveRequest->approved_at->format('Y-m-d H:i') }}</small>
                            </div>
                        @endif

                        @if ($leaveRequest->status === 'rejected' && $leaveRequest->approver)
                            <div class="alert alert-danger border-0 mb-3">
                                <strong>{{ __('hr::hr.rejected_by') }}:</strong>
                                <p class="mb-1 font-hold">{{ $leaveRequest->approver->name }}</p>
                                <small class="text-muted">{{ $leaveRequest->updated_at->format('Y-m-d H:i') }}</small>
                            </div>
                        @endif

                        @if ($leaveRequest->status === 'cancelled')
                            <div class="alert alert-warning border-0 mb-3">
                                <strong>{{ __('hr::hr.cancel_date') }}:</strong>
                                <div>
                                    <small class="text-muted">{{ $leaveRequest->updated_at->format('Y-m-d H:i') }}</small>
                                </div>
                            </div>
                        @endif

                        <div class="d-grid gap-2">
                            @if ($canSubmit)
                                <button type="button" class="btn btn-main w-100 hr-lr-action" data-action="submit"
                                        data-confirm="{{ __('hr::hr.confirm_submit_leave_request') }}">
                                    <i class="fas fa-paper-plane me-2"></i>{{ __('hr::hr.submit_request') }}
                                </button>
                            @endif
                            @if ($canApprove)
                                <button type="button" class="btn btn-success w-100 hr-lr-action" data-action="approve"
                                        data-confirm="{{ __('hr::hr.confirm_approve_leave_request') }}">
                                    <i class="fas fa-check me-2"></i>{{ __('hr::hr.approve_request') }}
                                </button>
                            @endif
                            @if ($canReject)
                                <button type="button" class="btn btn-danger w-100 hr-lr-action" data-action="reject"
                                        data-confirm="{{ __('hr::hr.confirm_reject_leave_request') }}">
                                    <i class="fas fa-times me-2"></i>{{ __('hr::hr.reject_request') }}
                                </button>
                            @endif
                            @if ($canEdit)
                                <a href="{{ route('hr.leaves.requests.edit', $leaveRequest) }}" class="btn btn-info w-100">
                                    <i class="fas fa-edit me-2"></i>{{ __('hr::hr.edit_leave_request') }}
                                </a>
                            @endif
                            @if ($canCancel)
                                <button type="button" class="btn btn-warning w-100 hr-lr-action" data-action="cancel"
                                        data-confirm="{{ __('hr::hr.confirm_cancel_leave_request') }}">
                                    <i class="fas fa-ban me-2"></i>{{ __('hr::hr.cancel_request') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window._hrLeaveRequestsConfig = {
        page: 'show',
        routes: {
            submit: @json(route('hr.leaves.requests.submit', $leaveRequest)),
            approve: @json(route('hr.leaves.requests.approve', $leaveRequest)),
            reject: @json(route('hr.leaves.requests.reject', $leaveRequest)),
            cancel: @json(route('hr.leaves.requests.cancel', $leaveRequest))
        },
        csrfToken: @json(csrf_token()),
        trans: {
            saveError: @json(__('hr::hr.error_loading'))
        }
    };
</script>
<script src="{{ asset('js/hr-leave-requests.js') }}"></script>
@endpush
