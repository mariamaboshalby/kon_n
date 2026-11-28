@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.hr_settings'),
        'breadcrumb_items' => [
            ['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')],
            ['label' => __('hr::hr.hr_settings'), 'url' => route('hr.settings.index')],
            ['label' => __('hr::hr.edit')],
        ],
    ])

    <div class="hr-settings-edit" style="font-family: var(--masar-font-family); direction: rtl;">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title font-hold fw-bold mb-0">{{ __('hr::hr.edit_hr_setting') }}</h3>
                <a href="{{ route('hr.settings.index') }}" class="btn btn-secondary font-hold fw-bold">
                    <i class="fas fa-arrow-left"></i>
                    {{ __('hr::hr.back_to_list') }}
                </a>
            </div>
            <div class="card-body">
                <form id="hrSettingForm" method="POST" action="{{ route('hr.settings.update') }}">
                    @csrf
                    @method('PUT')

                    {{-- الإجازات --}}
                    <div class="mb-3">
                        <label for="company_max_leave_percentage" class="form-label font-hold fw-bold">
                            {{ __('hr::hr.company_max_leave_percentage') }} (%) <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               name="company_max_leave_percentage"
                               id="company_max_leave_percentage"
                               value="{{ old('company_max_leave_percentage', $setting->company_max_leave_percentage) }}"
                               class="form-control font-hold fw-bold @error('company_max_leave_percentage') is-invalid @enderror"
                               required>
                        <small class="form-text text-muted font-hold">{{ __('hr::hr.company_max_leave_percentage_help') }}</small>
                        @error('company_max_leave_percentage')
                            <div class="invalid-feedback font-hold fw-bold">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="alert alert-info font-hold">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>{{ __('hr::hr.note') }}:</strong> {{ __('hr::hr.company_max_leave_percentage_note') }}
                    </div>

                    <div class="text-end mt-4">
                        <a href="{{ route('hr.settings.index') }}" class="btn btn-secondary font-hold fw-bold">{{ __('hr::hr.cancel') }}</a>
                        <button type="submit" class="btn btn-main font-hold fw-bold" id="hrSettingSubmitBtn">{{ __('hr::hr.update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function ($) {
    'use strict';

    // ── منع إرسال النموذج مرتين
    var submitting = false;
    $('#hrSettingForm').on('submit', function () {
        if (submitting) return false;
        submitting = true;
        $('#hrSettingSubmitBtn').prop('disabled', true);
        return true;
    });
})(jQuery);
</script>
@endpush

