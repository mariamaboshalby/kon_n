@extends('admin.dashboard')

{{-- Dynamic Sidebar: عرض كل الـ sections للصفحة الرئيسية --}}
@section('sidebar')
    @include('components.sidebar.items')
    @include('components.sidebar.discounts')
    @include('components.sidebar.permissions')
    @include('components.sidebar.crm')
    @include('components.sidebar.projects')
    @include('components.sidebar.departments')
    @include('components.sidebar.settings')
    @include('components.sidebar.daily_progress')
    @include('components.sidebar.inquiries')
@endsection

@section('content')
    @include('dashboard.charts')
@endsection
