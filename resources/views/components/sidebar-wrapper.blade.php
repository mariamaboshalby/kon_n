{{-- Dynamic Sidebar Wrapper Component --}}
@php
    // الـ sections اللي هيتم عرضها (يتم تمريرها من الصفحة)
    $sections = $sections ?? ['all'];
    $showAll = in_array('all', $sections);
@endphp

<!-- Left Sidenav -->
<div class="left-sidenav">
    <div class="menu-content h-100" data-simplebar>
        <ul class="metismenu left-sidenav-menu">

            {{-- Header ثابت --}}
            <li class="menu-label my-2">
                <a href="{{ route('home') }}">{{ config('public_settings.campany_name') }}</a>
            </li>

            <li class="nav-item border-bottom pb-1 mb-2">
                <a href="{{ route('admin.dashboard') }}"
                    class="nav-link d-flex align-items-center gap-2 font-hold fw-bold">
                    <i data-feather="home" style="color:#4e73df" class="menu-icon"></i>
                    {{ __('navigation.home') }}
                </a>
            </li>

            {{-- عرض الـ Sections المحددة فقط --}}
            @if($showAll)
                {{-- عرض كل شيء (للصفحة الرئيسية مثلاً) --}}
                @include('components.sidebar.items')
                {{-- @include('components.sidebar.discounts') --}}
                @include('components.sidebar.permissions')
                @include('components.sidebar.crm')
                @include('components.sidebar.projects')
                @include('components.sidebar.departments')
                @include('components.sidebar.settings')
                @include('components.sidebar.daily_progress')
                @include('components.sidebar.inquiries')
            @else
                {{-- عرض الـ sections المحددة فقط --}}
                @foreach($sections as $section)
                    @if(View::exists("components.sidebar.{$section}"))
                        @include("components.sidebar.{$section}")
                    @endif
                @endforeach
            @endif

        </ul>
    </div>
</div>

