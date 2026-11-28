@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.edit'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.flexible_salary_processing'), 'url' => route('hr.flexible-salary.processing.index')],
            ['label' => __('hr::hr.edit')],
        ],
    ])

    <div style="font-family: var(--masar-font-family); direction: rtl;">
        <div id="hrFlexAlert" class="alert d-none" role="alert"></div>
        <div class="card">
            <div class="card-body">
                <p class="font-hold fw-bold">{{ $processing->employee->name ?? '' }}</p>
                <form id="hrFlexEditForm">
                    <div class="mb-3">
                        <label class="form-label">{{ __('hr::hr.hours_worked') }}</label>
                        <input type="text" name="hours_worked" class="form-control" value="{{ $processing->hours_worked }}" inputmode="decimal" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('hr::hr.notes') }}</label>
                        <input type="text" name="notes" class="form-control" value="{{ $processing->notes }}">
                    </div>
                    <button type="submit" class="btn btn-main" id="hrFlexSubmitBtn">{{ __('hr::hr.update') }}</button>
                    <a href="{{ route('hr.flexible-salary.processing.index') }}" class="btn btn-secondary">{{ __('hr::hr.cancel') }}</a>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window._hrFlexibleSalaryConfig = {
        mode: 'edit',
        routes: {
            update: @json(route('hr.flexible-salary.processing.update', $processing->id))
        },
        csrfToken: @json(csrf_token())
    };
</script>
<script src="{{ asset('js/hr-flexible-salary.js') }}"></script>
@endpush
