@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.estate')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('estate::estate.properties'),
        'breadcrumb_items' => [
            ['label' => __('estate::estate.dashboard'), 'url' => route('admin.dashboard')],
            ['label' => __('estate::estate.properties')],
        ],
    ])

    <div class="row">
        <div class="col-lg-12">

            @if (session('message'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('message') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    @livewire('estate::property-list')
                </div>
            </div>
        </div>
    </div>
@endsection
