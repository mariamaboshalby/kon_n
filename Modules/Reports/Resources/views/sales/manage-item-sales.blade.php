@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.reports')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('reports::reports.item_sales_report'),
        'breadcrumb_items' => [
            ['label' => __('reports::reports.home'), 'url' => route('admin.dashboard')],
            ['label' => __('reports::reports.item_sales_report')],
        ],
    ])

    <div class="container-fluid">
        @livewire('sales.manage-item-sales')
    </div>
@endsection
