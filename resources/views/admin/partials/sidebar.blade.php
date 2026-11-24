@php
    $section = request('sidebar') ?? session('sidebar', 'all');
    $map = [
        'main' => null,
        'breadcrumb_items' => ['components.sidebar.items'],
        'discounts' => ['components.sidebar.discounts'],
        'permissions' => ['components.sidebar.permissions'],
        'crm' => ['components.sidebar.crm'],
        'projects' => ['components.sidebar.projects'],
        'departments' => ['components.sidebar.departments'],
        'settings' => ['components.sidebar.settings'],
        'daily_progress' => ['components.sidebar.daily_progress'],
        'inquiries' => ['components.sidebar.inquiries'],
    ];
    $allowed = $section === 'all' ? 'all' : $map[$section] ?? [];
@endphp

<!-- Left Sidenav -->
<div class="left-sidenav">

    <div class="menu-content h-100" data-simplebar>
        <ul class="metismenu left-sidenav-menu">

            <li class="menu-label my-2"><a href="{{ route('home') }}">{{ config('public_settings.campany_name') }}</a>
            </li>

            <li class="nav-item border-bottom pb-1 mb-2">
                <a href="{{ route('admin.dashboard') }}"
                    class="nav-link d-flex align-items-center gap-2 font-hold fw-bold">
                    <i data-feather="home" style="color:#4e73df" class="menu-icon"></i>
                    {{ __('navigation.home') }}
                </a>

            </li>

            @if ($section !== 'all')
                <li class="nav-item mb-2">
                    <div class="alert alert-info d-flex align-items-center justify-content-between"
                        style="margin: 0; padding: 0.5rem 0.75rem;">
                        <small class="mb-0">
                            <i data-feather="filter" style="width: 14px; height: 14px;" class="me-1"></i>
                            عرض: {{ $section }}
                        </small>
                        {{-- <a href="{{ request()->url() }}?sidebar=all" class="btn btn-sm btn-outline-primary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                            عرض الجميع
                        </a> --}}
                    </div>
                </li>
            @endif

            @if (View::hasSection('sidebar-filter'))
                @yield('sidebar-filter')
            @else
                @if ($allowed === 'all' || in_array('components.sidebar.items', $allowed))
                    @include('components.sidebar.items')
                @endif
                @if ($allowed === 'all' || in_array('components.sidebar.discounts', $allowed))
                    @include('components.sidebar.discounts')
                @endif
                @if ($allowed === 'all' || in_array('components.sidebar.permissions', $allowed))
                    @include('components.sidebar.permissions')
                @endif
                @if ($allowed === 'all' || in_array('components.sidebar.crm', $allowed))
                    @include('components.sidebar.crm')
                @endif
                @if ($allowed === 'all' || in_array('components.sidebar.projects', $allowed))
                    @include('components.sidebar.projects')
                @endif
                @if ($allowed === 'all' || in_array('components.sidebar.departments', $allowed))
                    @include('components.sidebar.departments')
                @endif
                @if ($allowed === 'all' || in_array('components.sidebar.settings', $allowed))
                    @include('components.sidebar.settings')
                @endif
                @if ($allowed === 'all' || in_array('components.sidebar.daily_progress', $allowed))
                    @include('components.sidebar.daily_progress')
                @endif
                @if ($allowed === 'all' || in_array('components.sidebar.inquiries', $allowed))
                    @include('components.sidebar.inquiries')
                @endif
            @endif

        </ul>
    </div>
</div>
