@can('view settings')
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 font-hold fw-bold transition-base" href="{{ route('mysettings.index') }}">
            <i class="las la-cog"></i>{{ __('settings::settings.nav_settings') }}
        </a>
    </li>
@endcan
@can('view Barcode Settings')
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 font-hold fw-bold transition-base" href="{{ route('barcode.print.settings.edit') }}">
            <i class="las la-barcode"></i>{{ __('settings::settings.nav_barcode_settings') }}
        </a>
    </li>
@endcan

@can('view Export Data')
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 font-hold fw-bold transition-base" href="{{ route('export-settings') }}">
            <i class="las la-database"></i>{{ __('settings::settings.nav_data_backup') }}
        </a>
    </li>
@endcan
