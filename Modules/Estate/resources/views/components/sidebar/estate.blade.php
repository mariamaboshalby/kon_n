<div class="sidebar-menu">
    <ul class="menu-list">
        <li class="menu-item {{ request()->routeIs('properties.*') ? 'active' : '' }}">
            <a href="{{ route('properties.index') }}" class="menu-link">
                <i class="las la-building"></i>
                <span>{{ __('estate::estate.properties') }}</span>
            </a>
        </li>
    </ul>
</div>
