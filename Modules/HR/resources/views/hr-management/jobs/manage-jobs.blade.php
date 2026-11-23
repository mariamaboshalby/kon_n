@extends('admin.dashboard')

{{-- Dynamic Sidebar --}}
@section('sidebar')
    @include('components.sidebar.departments')
@endsection
@section('content')
    @include('components.breadcrumb', [
        'title' => __('hr::hr.jobs'),
        'breadcrumb_items' => [['label' => __('hr::hr.home'), 'url' => route('admin.dashboard')], ['label' => __('hr::hr.jobs')]],
    ])


<livewire:hr::hr-management.jobs.manage-jobs />
 
@endsection
