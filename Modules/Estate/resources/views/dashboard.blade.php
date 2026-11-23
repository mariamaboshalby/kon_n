@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.estate')
@endsection

@section('content')
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
    .stat-card {
        border-radius: 16px;
        border: none;
        overflow: hidden;
        transition: transform .2s, box-shadow .2s;
    }
    .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,.12); }
    .stat-card .icon-box {
        width: 56px; height: 56px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.6rem; flex-shrink: 0;
    }
    .stat-card .stat-value { font-size: 2rem; font-weight: 700; line-height: 1; }
    .stat-card .stat-label { font-size: .8rem; color: #6c757d; margin-top: 4px; }

    .city-bar { height: 8px; border-radius: 4px; background: #e9ecef; overflow: hidden; }
    .city-bar-fill { height: 100%; border-radius: 4px; background: linear-gradient(90deg,#34d3a3,#28a745); }

    .prop-card {
        border-radius: 12px; border: 1px solid #e9ecef;
        transition: box-shadow .2s;
    }
    .prop-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,.1); }
    .prop-card .badge-status {
        position: absolute; top: 10px; right: 10px;
        font-size: .7rem; padding: 4px 10px; border-radius: 20px;
    }
    .prop-card .prop-img {
        height: 140px; background: linear-gradient(135deg,#667eea,#764ba2);
        border-radius: 12px 12px 0 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 3rem; color: rgba(255,255,255,.6);
    }

    .donut-ring { transform: rotate(-90deg); }
    .section-title {
        font-size: 1rem; font-weight: 700; color: #344767;
        border-right: 4px solid #34d3a3; padding-right: 10px;
    }
</style>
@endpush

    @include('components.breadcrumb', [
        'title' => __('estate::estate.dashboard_title'),
        'breadcrumb_items' => [
            ['label' => __('estate::estate.dashboard'), 'url' => route('admin.dashboard')],
            ['label' => __('estate::estate.module_name')],
        ],
    ])

    {{-- ══════════════════════════════════════════════════════════════════════
         ROW 1 : KPI Cards
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">

        {{-- Total --}}
        <div class="col-6 col-md-3">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon-box" style="background:#e8f5e9">
                        <i class="las la-building" style="color:#2e7d32"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ number_format($stats['total']) }}</div>
                        <div class="stat-label">{{ __('estate::estate.total_properties') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Available --}}
        <div class="col-6 col-md-3">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon-box" style="background:#e3f2fd">
                        <i class="las la-check-circle" style="color:#1565c0"></i>
                    </div>
                    <div>
                        <div class="stat-value text-primary">{{ number_format($stats['available']) }}</div>
                        <div class="stat-label">{{ __('estate::estate.status_available') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reserved --}}
        <div class="col-6 col-md-3">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon-box" style="background:#fff8e1">
                        <i class="las la-clock" style="color:#f57f17"></i>
                    </div>
                    <div>
                        <div class="stat-value text-warning">{{ number_format($stats['reserved']) }}</div>
                        <div class="stat-label">{{ __('estate::estate.status_reserved') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sold --}}
        <div class="col-6 col-md-3">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon-box" style="background:#fce4ec">
                        <i class="las la-handshake" style="color:#c62828"></i>
                    </div>
                    <div>
                        <div class="stat-value text-danger">{{ number_format($stats['sold']) }}</div>
                        <div class="stat-label">{{ __('estate::estate.status_sold') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         ROW 2 : Financial + Listing + Clients
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">

        {{-- Total Value --}}
        <div class="col-md-4">
            <div class="card stat-card shadow-sm h-100"
                 style="background:linear-gradient(135deg,#34d3a3,#28a745);color:#fff">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div style="font-size:.8rem;opacity:.85">{{ __('estate::estate.total_portfolio_value') }}</div>
                            <div style="font-size:1.8rem;font-weight:700;line-height:1.2">
                                {{ number_format($totalValue, 0) }}
                            </div>
                            <div style="font-size:.75rem;opacity:.8">{{ __('estate::estate.currency') }}</div>
                        </div>
                        <i class="las la-coins" style="font-size:3rem;opacity:.4"></i>
                    </div>
                    <div class="mt-3 d-flex gap-3" style="font-size:.8rem">
                        <div>
                            <div style="opacity:.8">{{ __('estate::estate.avg_price') }}</div>
                            <div class="fw-bold">{{ number_format($avgPrice, 0) }}</div>
                        </div>
                        <div>
                            <div style="opacity:.8">{{ __('estate::estate.total_commission') }}</div>
                            <div class="fw-bold">{{ number_format($totalCommission, 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Listing Types --}}
        <div class="col-md-4">
            <div class="card shadow-sm h-100" style="border-radius:16px;border:none">
                <div class="card-body">
                    <div class="section-title mb-3">{{ __('estate::estate.listing_type') }}</div>
                    @php $maxListing = max(array_values($byListing)) ?: 1; @endphp
                    @foreach([
                        ['sale',          __('estate::estate.listing_type_sale'),          '#1565c0'],
                        ['rent',          __('estate::estate.listing_type_rent'),          '#2e7d32'],
                        ['furnished_rent',__('estate::estate.listing_type_furnished_rent'),'#f57f17'],
                    ] as [$key, $label, $color])
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span style="font-size:.85rem">{{ $label }}</span>
                                <span class="fw-bold" style="color:{{ $color }}">{{ $byListing[$key] }}</span>
                            </div>
                            <div class="city-bar">
                                <div class="city-bar-fill"
                                     style="width:{{ $maxListing ? round($byListing[$key]/$maxListing*100) : 0 }}%;background:{{ $color }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Clients + Types --}}
        <div class="col-md-4">
            <div class="card shadow-sm h-100" style="border-radius:16px;border:none">
                <div class="card-body">
                    <div class="section-title mb-3">{{ __('estate::estate.overview') }}</div>

                    <div class="d-flex align-items-center gap-3 mb-3 p-3 rounded-3" style="background:#f8f9fa">
                        <i class="las la-users" style="font-size:2rem;color:#34d3a3"></i>
                        <div>
                            <div style="font-size:1.5rem;font-weight:700">{{ $clientsCount }}</div>
                            <div style="font-size:.8rem;color:#6c757d">{{ __('estate::estate.estate_clients') }}</div>
                        </div>
                    </div>

                    @foreach($byType->take(4) as $type)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span style="font-size:.85rem">
                                <i class="{{ $type->icon }} me-1" style="color:#34d3a3"></i>
                                {{ $type->name }}
                            </span>
                            <span class="badge rounded-pill" style="background:#e8f5e9;color:#2e7d32">
                                {{ $type->properties_count }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         ROW 3 : By City + Recent Properties
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">

        {{-- By City --}}
        <div class="col-md-4">
            <div class="card shadow-sm h-100" style="border-radius:16px;border:none">
                <div class="card-body">
                    <div class="section-title mb-3">{{ __('estate::estate.top_cities') }}</div>
                    @php $maxCity = $byCity->max() ?: 1; @endphp
                    @forelse($byCity as $city => $count)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span style="font-size:.85rem"><i class="las la-map-marker me-1 text-muted"></i>{{ $city }}</span>
                                <span class="fw-bold text-primary">{{ $count }}</span>
                            </div>
                            <div class="city-bar">
                                <div class="city-bar-fill" style="width:{{ round($count/$maxCity*100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small">{{ __('estate::estate.no_data_found') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Recent Properties --}}
        <div class="col-md-8">
            <div class="card shadow-sm h-100" style="border-radius:16px;border:none">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="section-title">{{ __('estate::estate.recent_properties') }}</div>
                        <a href="{{ route('properties.index') }}" class="btn btn-sm btn-outline-primary" style="border-radius:20px">
                            {{ __('estate::estate.view') }} <i class="las la-arrow-left ms-1"></i>
                        </a>
                    </div>
                    <div class="row g-3">
                        @forelse($recent as $property)
                            @php
                                $gradients = [
                                    'linear-gradient(135deg,#667eea,#764ba2)',
                                    'linear-gradient(135deg,#f093fb,#f5576c)',
                                    'linear-gradient(135deg,#4facfe,#00f2fe)',
                                    'linear-gradient(135deg,#43e97b,#38f9d7)',
                                    'linear-gradient(135deg,#fa709a,#fee140)',
                                    'linear-gradient(135deg,#a18cd1,#fbc2eb)',
                                ];
                                $grad = $gradients[$loop->index % count($gradients)];
                                $statusColor = match($property->status->value) {
                                    'available' => '#2e7d32', 'reserved' => '#f57f17', default => '#6c757d'
                                };
                                $firstImage = $property->getFirstMediaUrl('property_images');
                            @endphp
                            <div class="col-6 col-lg-4">
                                <a href="{{ route('properties.show', $property) }}" class="text-decoration-none">
                                    <div class="prop-card position-relative">
                                        @if($firstImage)
                                            <div class="prop-img" style="background:url('{{ $firstImage }}') center/cover no-repeat;"></div>
                                        @else
                                            <div class="prop-img" style="background:{{ $grad }}">
                                                <i class="las la-building"></i>
                                            </div>
                                        @endif
                                        <span class="badge-status text-white" style="background:{{ $statusColor }}">
                                            {{ $property->status->label() }}
                                        </span>
                                        <div class="p-2">
                                            <div class="fw-bold text-dark" style="font-size:.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                                {{ $property->title }}
                                            </div>
                                            <div class="text-muted" style="font-size:.75rem">
                                                <i class="las la-map-marker me-1"></i>{{ $property->city }}
                                            </div>
                                            <div class="text-primary fw-bold mt-1" style="font-size:.85rem">
                                                {{ number_format((float)$property->price, 0) }} {{ __('estate::estate.currency') }}
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @empty
                            <div class="col-12 text-center text-muted py-4">
                                {{ __('estate::estate.no_properties_found') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         ROW 4 : Map
    ══════════════════════════════════════════════════════════════════════ --}}
    @if($mapProperties->count())
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card shadow-sm" style="border-radius:16px;border:none">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="section-title">
                            <i class="las la-map me-1"></i>{{ __('estate::estate.properties_map') }}
                        </div>
                        <span class="badge rounded-pill bg-primary">
                            {{ $mapProperties->count() }} {{ __('estate::estate.properties') }}
                        </span>
                    </div>
                    <div id="estate-map" style="height:420px;border-radius:12px;z-index:1;"></div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════
         Quick Actions
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="row g-3">
        <div class="col-12">
            <div class="card shadow-sm" style="border-radius:16px;border:none">
                <div class="card-body">
                    <div class="section-title mb-3">{{ __('estate::estate.quick_actions') }}</div>
                    <div class="d-flex flex-wrap gap-2">
                        @can('create Properties')
                            <a href="{{ route('properties.create') }}" class="btn btn-primary" style="border-radius:20px">
                                <i class="las la-plus me-1"></i>{{ __('estate::estate.add_property') }}
                            </a>
                        @endcan
                        @can('create Clients')
                            <a href="{{ route('estate.clients.create') }}" class="btn btn-success" style="border-radius:20px">
                                <i class="las la-user-plus me-1"></i>{{ __('estate::estate.add_new_client') }}
                            </a>
                        @endcan
                        <a href="{{ route('estate.clients.index') }}" class="btn btn-outline-primary" style="border-radius:20px">
                            <i class="las la-users me-1"></i>{{ __('estate::estate.estate_clients') }}
                        </a>
                        <a href="{{ route('estate.contacts.index') }}" class="btn btn-outline-secondary" style="border-radius:20px">
                            <i class="las la-address-book me-1"></i>{{ __('estate::estate.contacts') }}
                        </a>
                        <a href="{{ route('estate.leads.index') }}" class="btn btn-outline-warning" style="border-radius:20px">
                            <i class="las la-funnel-dollar me-1"></i>{{ __('estate::estate.leads') }}
                        </a>
                        @can('view PropertyTypes')
                            <a href="{{ route('property-types.index') }}" class="btn btn-outline-info" style="border-radius:20px">
                                <i class="las la-sitemap me-1"></i>{{ __('estate::estate.property_types') }}
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@if($mapProperties->count())
@section('script')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    var props = @json($mapProperties);
    var map = L.map('estate-map');

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors', maxZoom: 19
    }).addTo(map);

    var colors = { available: '#2e7d32', reserved: '#f57f17', sold: '#c62828' };
    var bounds = [];

    props.forEach(function (p) {
        var c = colors[p.status] || '#1565c0';
        var icon = L.divIcon({
            className: '',
            html: '<div style="background:' + c + ';width:16px;height:16px;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.4)"></div>',
            iconSize: [16, 16], iconAnchor: [8, 8]
        });

        L.marker([p.lat, p.lng], { icon: icon }).addTo(map).bindPopup(
            '<div style="min-width:190px;font-family:inherit">' +
            '<div style="font-weight:700;font-size:.9rem;margin-bottom:4px">' + p.title + '</div>' +
            '<div style="font-size:.78rem;color:#666;margin-bottom:6px"><i class="las la-map-marker"></i> ' + p.city + ', ' + p.district + '</div>' +
            '<div style="display:flex;gap:5px;margin-bottom:6px">' +
            '<span style="background:' + c + ';color:#fff;padding:2px 8px;border-radius:20px;font-size:.72rem">' + p.status_label + '</span>' +
            '<span style="background:#e3f2fd;color:#1565c0;padding:2px 8px;border-radius:20px;font-size:.72rem">' + p.listing_type + '</span>' +
            '</div>' +
            '<div style="font-weight:700;color:#2e7d32;font-size:.95rem;margin-bottom:8px">' + p.price + ' {{ __("estate::estate.currency") }}</div>' +
            '<a href="' + p.url + '" style="display:block;text-align:center;background:#1565c0;color:#fff;padding:5px;border-radius:8px;text-decoration:none;font-size:.8rem">{{ __("estate::estate.view") }}</a>' +
            '</div>'
        );
        bounds.push([p.lat, p.lng]);
    });

    if (bounds.length === 1) { map.setView(bounds[0], 14); }
    else if (bounds.length > 1) { map.fitBounds(bounds, { padding: [50, 50] }); }
    else { map.setView([24.7136, 46.6753], 6); }

    // Legend
    var legend = L.control({ position: 'bottomright' });
    legend.onAdd = function () {
        var d = L.DomUtil.create('div');
        d.style.cssText = 'background:#fff;padding:10px 14px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.15);font-size:.8rem;line-height:2';
        d.innerHTML =
            '<b>{{ __("estate::estate.status") }}</b><br>' +
            '<span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#2e7d32;vertical-align:middle;margin-left:4px"></span> {{ __("estate::estate.status_available") }}<br>' +
            '<span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#f57f17;vertical-align:middle;margin-left:4px"></span> {{ __("estate::estate.status_reserved") }}<br>' +
            '<span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#c62828;vertical-align:middle;margin-left:4px"></span> {{ __("estate::estate.status_sold") }}';
        return d;
    };
    legend.addTo(map);
})();
</script>
@endsection
@endif
