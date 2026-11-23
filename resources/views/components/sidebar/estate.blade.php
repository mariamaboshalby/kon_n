{{-- ═══ العقارات ═══ --}}
@can('view Properties')
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 font-hold fw-bold transition-base {{ request()->routeIs('estate.dashboard') ? 'active' : '' }}"
           href="{{ route('estate.dashboard') }}">
            <i class="las la-tachometer-alt"></i>{{ __('estate::estate.dashboard_title') }}
        </a>
    </li>
@endcan

@can('view Properties')
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 font-hold fw-bold transition-base {{ request()->routeIs('properties.*') ? 'active' : '' }}"
           href="{{ route('properties.index') }}">
            <i class="las la-building"></i>{{ __('estate::estate.properties') }}
        </a>
    </li>
@endcan

@can('view Properties')
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 font-hold fw-bold transition-base {{ request()->routeIs('estate.clients.*') ? 'active' : '' }}"
           href="{{ route('estate.clients.index') }}">
            <i class="las la-users"></i>{{ __('estate::estate.estate_clients') }}
        </a>
    </li>
@endcan

@can('view Properties')
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 font-hold fw-bold transition-base {{ request()->routeIs('estate.contacts.*') ? 'active' : '' }}"
           href="{{ route('estate.contacts.index') }}">
            <i class="las la-address-book"></i>{{ __('estate::estate.contacts') }}
        </a>
    </li>
@endcan

@can('view Properties')
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 font-hold fw-bold transition-base {{ request()->routeIs('estate.leads.*') ? 'active' : '' }}"
           href="{{ route('estate.leads.index') }}">
            <i class="las la-funnel-dollar"></i>{{ __('estate::estate.leads') }}
        </a>
    </li>
@endcan

@can('create Clients')
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 font-hold fw-bold transition-base {{ request()->routeIs('estate.clients.create') ? 'active' : '' }}"
           href="{{ route('estate.clients.create') }}">
            <i class="las la-user-plus"></i>{{ __('estate::estate.add_new_client') }}
        </a>
    </li>
@endcan

@can('view PropertyTypes')
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 font-hold fw-bold transition-base {{ request()->routeIs('property-types.*') ? 'active' : '' }}"
           href="{{ route('property-types.index') }}">
            <i class="las la-sitemap"></i>{{ __('estate::estate.property_types') }}
        </a>
    </li>
@endcan
