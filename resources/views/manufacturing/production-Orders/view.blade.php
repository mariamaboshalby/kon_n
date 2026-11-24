@extends('admin.dashboard')

{{-- Dynamic Sidebar --}}
@section('sidebar')
@endsection

@section('content')
<livewire:production-orders-management.view :productionOrder="$productionOrder" />
@endsection
