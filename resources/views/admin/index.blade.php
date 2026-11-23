@extends('admin.dashboard')

@section('content')
    {{-- Dashboard Header --}}
    <div class="col-12 mb-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="mb-1 fw-bold">
                    <i class="fas fa-tachometer-alt me-2" style="color:#34d3a3;"></i>
                    {{ __('navigation.home') }}
                </h5>
                <p class="text-muted mb-0 small">
                    {{ \Carbon\Carbon::now()->translatedFormat('l، d F Y') }}
                </p>
            </div>
            <span class="badge" style="background:#34d3a3; color:#fff;">
                <i class="fas fa-circle me-1" style="font-size:8px;"></i>
                {{ auth()->user()->name }}
            </span>
        </div>
        <hr class="mt-2 mb-0" style="border-color:#34d3a3; opacity:0.25;">
    </div>

    <div class="col-12">
        @include('dashboard.index')
    </div>
@endsection
