<?php declare(strict_types=1); ?>

@extends('admin.dashboard')

@section('sidebar')
    @include('currencies::components.sidebar')
@endsection

@section('content')
    <div class="container-fluid p-3">
        @yield('content')
    </div>
@endsection
