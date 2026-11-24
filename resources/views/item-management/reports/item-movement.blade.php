@extends('admin.dashboard')

{{-- Dynamic Sidebar --}}
@section('sidebar')
@endsection
@section('content')

    <livewire:item-management.reports.item-movement :itemId="$itemId" :warehouseId="$warehouseId" />
@endsection
