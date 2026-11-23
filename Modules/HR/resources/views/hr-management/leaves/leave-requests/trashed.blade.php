@extends('admin.dashboard')

{{-- Dynamic Sidebar --}}
@section('sidebar')
    @include('components.sidebar.departments')
@endsection
@section('content')
    @include('components.breadcrumb', [
        'title' => __('الطلبات المحذوفة'),
        'breadcrumb_items' => [
            ['label' => __('Home'), 'url' => route('admin.dashboard')],
            ['label' => __('إدارة طلب الإجازات'), 'url' => route('hr.leaves.requests.index')],
            ['label' => __('الطلبات المحذوفة')]
        ],
    ])

<livewire:hr::leaves.leave-requests.trashed />
 
@endsection
