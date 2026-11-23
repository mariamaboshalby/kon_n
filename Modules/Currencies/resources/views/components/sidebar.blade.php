<?php declare(strict_types=1); ?>

@can('view Currencies')
    <li class="nav-item">
        {{-- <a class="nav-link d-flex align-items-center gap-2 font-hold fw-bold transition-base {{ request()->routeIs('currencies.*') ? 'active' : '' }}" 
           href="{{ route('currencies.index') }}"
           data-bs-toggle="collapse"
           data-bs-target="#currenciesMenu"
           aria-expanded="{{ request()->routeIs('currencies.*') ? 'true' : 'false' }}">
            <i class="las la-dollar-sign"></i>
            <span>{{ __('currencies::currencies.currencies') }}</span>
            <i class="las la-chevron-down ms-auto"></i>
        </a> --}}
        <div class="{{ request()->routeIs('currencies.*') ? 'show' : '' }}" id="currenciesMenu">
            <ul class="nav flex-column ms-3 mt-2">
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('currencies.index') ? 'active' : '' }}" 
                       href="{{ route('currencies.index') }}">
                        <i class="las la-dollar-sign"></i>
                        <span>{{ __('currencies::currencies.currencies') }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('currencies.convert') ? 'active' : '' }}" 
                       href="{{ route('currencies.convert') }}">
                        <i class="las la-exchange-alt"></i>
                        <span>{{ __('currencies::currencies.convert') }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('currencies.revaluation') ? 'active' : '' }}" 
                       href="{{ route('currencies.revaluation') }}">
                        <i class="las la-balance-scale"></i>
                        <span>{{ __('currencies::currencies.cash_box_revaluation') }}</span>
                    </a>
                </li>
            </ul>
        </div>
    </li>
@endcan
