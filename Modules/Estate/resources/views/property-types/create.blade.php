@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.estate')
@endsection

@section('content')
    @push('styles')
    <style>
        .form-label.required::after { content: ' *'; color: #dc3545; font-weight: bold; }
    </style>
    @endpush

    @include('components.breadcrumb', [
        'title' => __('estate::estate.add_property_type'),
        'breadcrumb_items' => [
            ['label' => __('common.dashboard'), 'url' => route('admin.dashboard')],
            ['label' => __('estate::estate.property_types'), 'url' => route('property-types.index')],
            ['label' => __('estate::estate.add_property_type')],
        ],
    ])

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="las la-plus-circle me-2"></i>
                        {{ __('estate::estate.add_property_type') }}
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('property-types.store') }}" method="POST">
                        @csrf
                        @include('estate::property-types._form')
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="las la-save me-1"></i> {{ __('common.save') }}
                            </button>
                            <a href="{{ route('property-types.index') }}" class="btn btn-secondary">
                                <i class="las la-times me-1"></i> {{ __('common.cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
