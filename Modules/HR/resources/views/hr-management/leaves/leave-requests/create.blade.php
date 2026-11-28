@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.add_leave_request'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.leave_requests'), 'url' => route('hr.leaves.requests.index')],
            ['label' => __('hr::hr.add_leave_request')],
        ],
    ])

    <div class="hr-leave-requests-form" style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrLeaveRequestAlert" class="alert d-none" role="alert"></div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title font-hold fw-bold mb-0">{{ __('hr::hr.add_leave_request') }}</h3>
                <a href="{{ route('hr.leaves.requests.index') }}" class="btn btn-secondary font-hold fw-bold">
                    <i class="fas fa-arrow-left"></i>
                    {{ __('hr::hr.back_to_list') }}
                </a>
            </div>
            <div class="card-body">
                @include('hr::hr-management.leaves.leave-requests.partials.form', [
                    'leaveRequest' => null,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'submitLabel' => __('hr::hr.save_request'),
                    'cancelUrl' => route('hr.leaves.requests.index'),
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
            store: @json(route('hr.leaves.requests.store')),
            update: '',
            preview: @json(route('hr.leaves.requests.preview'))
        },
        isEdit: false,
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
