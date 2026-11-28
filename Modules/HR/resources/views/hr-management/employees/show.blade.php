@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.departments')
@endsection

@section('content')
    <div style="font-family: var(--masar-font-family); direction: rtl;">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 font-hold fw-bold">
                            <i class="fas fa-user me-2"></i>{{ __('hr::hr.view_employee') }}
                        </h5>
                        <div class="d-flex gap-2">
                            @can('edit Employees')
                                <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-success">
                                    <i class="fas fa-edit me-2"></i>{{ __('hr::hr.edit') }}
                                </a>
                            @endcan
                            <a href="{{ route('employees.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>{{ __('hr::hr.back_to_list') }}
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @include('hr::hr-management.employees.partials.view.employee-view', [
                            'viewEmployee' => $viewEmployee ?? $employee,
                        ])
                    </div>
                </div>
            </div>
        </div>

        <div id="imageLightbox" class="position-fixed top-0 start-0 w-100 h-100 align-items-center justify-content-center"
            style="z-index: 10000; background-color: rgba(0,0,0,0.9); display: none;"
            onclick="closeLightbox()">
            <button type="button"
                class="btn-close btn-close-white position-absolute top-0 end-0 m-4"
                onclick="closeLightbox()"
                aria-label="{{ __('hr::hr.close') }}"></button>
            <img id="lightboxImage"
                src=""
                class="img-fluid"
                style="max-height: 90vh; max-width: 90vw;"
                onclick="event.stopPropagation()"
                alt="{{ __('hr::hr.employee_image') }}">
        </div>
    </div>
@endsection

@push('styles')
    @include('hr::hr-management.employees.partials.view.style')
@endpush

@push('scripts')
<script>
function openLightbox(imageUrl) {
    document.getElementById('lightboxImage').src = imageUrl;
    document.getElementById('imageLightbox').style.display = 'flex';
}
function closeLightbox() {
    document.getElementById('imageLightbox').style.display = 'none';
}
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeLightbox();
});
</script>
@endpush
