<li class="menu-title mt-2">
    <i class="las la-coins me-1"></i>
    {{ __('settings::settings.currency_exchange_module_title') }}
</li>

@can('view Currency Exchange')
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 font-hold fw-bold transition-base {{ request()->routeIs('settings.currency-exchange.index') ? 'active' : '' }}"
            href="{{ route('settings.currency-exchange.index') }}"
            style="{{ request()->routeIs('settings.currency-exchange.index') ? 'background-color: rgba(52, 211, 163, 0.1); color: #34d3a3;' : '' }}">
            <i class="las la-list font-18"></i>
            {{ __('settings::settings.all_exchange_operations') }}
        </a>
    </li>
@endcan

@can('view Currency Exchange')
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 font-hold fw-bold transition-base {{ request()->routeIs('settings.buy-currency.*') ? 'active' : '' }}"
            href="{{ route('settings.buy-currency.index') }}"
            style="{{ request()->routeIs('settings.buy-currency.*') ? 'background-color: rgba(25, 135, 84, 0.1); color: #198754;' : '' }}">
            <i class="las la-shopping-cart font-18 text-success"></i>
            {{ __('settings::settings.buy_currency') }}
        </a>
    </li>
@endcan

@can('view Currency Exchange')
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 font-hold fw-bold transition-base {{ request()->routeIs('settings.sell-currency.*') ? 'active' : '' }}"
            href="{{ route('settings.sell-currency.index') }}"
            style="{{ request()->routeIs('settings.sell-currency.*') ? 'background-color: rgba(13, 202, 240, 0.1); color: #0dcaf0;' : '' }}">
            <i class="las la-hand-holding-usd font-18 text-info"></i>
            {{ __('settings::settings.sell_currency') }}
        </a>
    </li>
@endcan

<li class="menu-title mt-3">
    <i class="las la-cog me-1"></i>
    {{ __('settings::settings.currency_settings_title') }}
</li>

@can('view Currencies')
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 font-hold fw-bold transition-base {{ request()->routeIs('currencies.index') ? 'active' : '' }}"
            href="{{ route('currencies.index') }}"
            style="{{ request()->routeIs('currencies.index') ? 'background-color: rgba(52, 211, 163, 0.1); color: #34d3a3;' : '' }}">
            <i class="las la-dollar-sign font-18"></i>
            {{ __('settings::settings.nav_currencies') }}
        </a>
    </li>
@endcan
