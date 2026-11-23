@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.estate')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => $property->title,
        'breadcrumb_items' => [
            ['label' => __('estate::estate.dashboard'), 'url' => route('admin.dashboard')],
            ['label' => __('estate::estate.properties'), 'url' => route('properties.index')],
            ['label' => $property->title],
        ],
    ])

    <div class="row">
        {{-- Property Details --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">
                        <i class="las la-building me-2"></i>
                        {{ $property->title }}
                    </h4>
                    <div class="d-flex gap-2">
                        @can('edit Properties')
                            <a href="{{ route('properties.edit', $property) }}" class="btn btn-sm btn-primary">
                                <i class="las la-edit"></i> {{ __('estate::estate.edit') }}
                            </a>
                        @endcan
                        @can('delete Properties')
                            <form action="{{ route('properties.destroy', $property) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" 
                                        onclick="return confirm('{{ __('estate::estate.confirm_delete_property') }}')">
                                    <i class="las la-trash"></i> {{ __('estate::estate.delete') }}
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    {{-- Basic Information --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="border-bottom pb-2 mb-3">
                                <i class="las la-info-circle me-2"></i>
                                {{ __('estate::estate.basic_information') }}
                            </h5>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('estate::estate.sku') }}:</strong>
                            <p class="mb-0"><code>{{ $property->sku }}</code></p>
                        </div>
                        @if($property->propertyType)
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('estate::estate.property_type') }}:</strong>
                            <p class="mb-0">
                                @if($property->propertyType->parent)
                                    <span class="text-muted">{{ $property->propertyType->parent->name }}</span>
                                    <i class="las la-angle-left mx-1 text-muted" style="font-size:.75rem"></i>
                                @endif
                                <span class="badge bg-light text-dark border">{{ $property->propertyType->name }}</span>
                            </p>
                        </div>
                        @endif
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('estate::estate.listing_type') }}:</strong>
                            <p class="mb-0">
                                <span class="badge bg-info">{{ $property->listing_type->label() }}</span>
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('estate::estate.status') }}:</strong>
                            <p class="mb-0">
                                <span class="badge bg-{{ $property->status->value === 'available' ? 'success' : ($property->status->value === 'reserved' ? 'warning' : 'secondary') }}">
                                    {{ $property->status->label() }}
                                </span>
                            </p>
                        </div>
                        @if($property->finishing_level)
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('estate::estate.finishing_level') }}:</strong>
                            <p class="mb-0">{{ $property->finishing_level->label() }}</p>
                        </div>
                        @endif
                    </div>

                    {{-- Location --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="border-bottom pb-2 mb-3">
                                <i class="las la-map-marker me-2"></i>
                                {{ __('estate::estate.location') }}
                            </h5>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('estate::estate.city') }}:</strong>
                            <p class="mb-0">{{ $property->city }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('estate::estate.district') }}:</strong>
                            <p class="mb-0">{{ $property->district }}</p>
                        </div>
                        @if($property->address_details)
                        <div class="col-md-12 mb-3">
                            <strong>{{ __('estate::estate.address_details') }}:</strong>
                            <p class="mb-0">{{ $property->address_details }}</p>
                        </div>
                        @endif
                        @if($property->landmark)
                        <div class="col-md-12 mb-3">
                            <strong>{{ __('estate::estate.landmark') }}:</strong>
                            <p class="mb-0">{{ $property->landmark }}</p>
                        </div>
                        @endif
                        @if($property->latitude && $property->longitude)
                        <div class="col-md-12 mb-3">
                            <strong>{{ __('estate::estate.coordinates') }}:</strong>
                            <p class="mb-0">
                                <a href="https://www.google.com/maps?q={{ $property->latitude }},{{ $property->longitude }}" target="_blank">
                                    {{ $property->latitude }}, {{ $property->longitude }}
                                    <i class="las la-external-link-alt"></i>
                                </a>
                            </p>
                        </div>
                        @endif
                    </div>

                    {{-- Specifications --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="border-bottom pb-2 mb-3">
                                <i class="las la-ruler-combined me-2"></i>
                                {{ __('estate::estate.specifications') }}
                            </h5>
                        </div>
                        <div class="col-md-4 mb-3">
                            <strong>{{ __('estate::estate.area') }}:</strong>
                            <p class="mb-0">{{ number_format((float) $property->area, 2) }} {{ __('estate::estate.sqm') }}</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <strong>{{ __('estate::estate.rooms_count') }}:</strong>
                            <p class="mb-0">{{ $property->rooms_count }}</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <strong>{{ __('estate::estate.bathrooms_count') }}:</strong>
                            <p class="mb-0">{{ $property->bathrooms_count }}</p>
                        </div>
                        @if($property->floor_number)
                        <div class="col-md-4 mb-3">
                            <strong>{{ __('estate::estate.floor_number') }}:</strong>
                            <p class="mb-0">{{ $property->floor_number }}</p>
                        </div>
                        @endif
                        @if($property->build_year)
                        <div class="col-md-4 mb-3">
                            <strong>{{ __('estate::estate.build_year') }}:</strong>
                            <p class="mb-0">{{ $property->build_year }}</p>
                        </div>
                        @endif
                        <div class="col-md-4 mb-3">
                            <strong>{{ __('estate::estate.is_furnished') }}:</strong>
                            <p class="mb-0">
                                @if($property->is_furnished)
                                    <span class="badge bg-success">{{ __('estate::estate.yes') }}</span>
                                    ({{ $property->furnishing_percentage }}%)
                                @else
                                    <span class="badge bg-secondary">{{ __('estate::estate.no') }}</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Financial Information --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="border-bottom pb-2 mb-3">
                                <i class="las la-money-bill me-2"></i>
                                {{ __('estate::estate.financial_information') }}
                            </h5>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('estate::estate.price') }}:</strong>
                            <p class="mb-0 h4 text-primary">{{ $property->formatted_price }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('estate::estate.payment_method') }}:</strong>
                            <p class="mb-0">{{ $property->payment_method->label() }}</p>
                        </div>
                        @if($property->installment_period_months)
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('estate::estate.installment_period_months') }}:</strong>
                            <p class="mb-0">{{ $property->installment_period_months }} {{ __('estate::estate.months') }}</p>
                        </div>
                        @endif
                        @if($property->commission)
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('estate::estate.commission') }}:</strong>
                            <p class="mb-0">{{ number_format((float) $property->commission, 2) }} {{ __('estate::estate.currency') }}</p>
                        </div>
                        @endif
                    </div>

                    {{-- Notes --}}
                    @if($property->neighbor_notes || $property->reputation_notes)
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="border-bottom pb-2 mb-3">
                                <i class="las la-sticky-note me-2"></i>
                                {{ __('estate::estate.notes') }}
                            </h5>
                        </div>
                        @if($property->neighbor_notes)
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('estate::estate.neighbor_notes') }}:</strong>
                            <p class="mb-0">{{ $property->neighbor_notes }}</p>
                        </div>
                        @endif
                        @if($property->reputation_notes)
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('estate::estate.reputation_notes') }}:</strong>
                            <p class="mb-0">{{ $property->reputation_notes }}</p>
                        </div>
                        @endif
                    </div>
                    @endif

                    {{-- Images Gallery --}}
                    @if($property->media->count() > 0)
                    <div class="row">
                        <div class="col-12">
                            <h5 class="border-bottom pb-2 mb-3">
                                <i class="las la-images me-2"></i>
                                {{ __('estate::estate.images') }}
                            </h5>
                        </div>
                        <div class="col-12">
                            <div class="row g-3">
                                @foreach($property->media as $media)
                                    <div class="col-md-4">
                                        <a href="{{ $media->getUrl() }}" target="_blank">
                                            <img src="{{ $media->getUrl() }}" class="img-fluid rounded" alt="{{ $property->title }}">
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar Info --}}
        <div class="col-lg-4">
            {{-- Stakeholders --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="las la-users me-2"></i>
                        {{ __('estate::estate.stakeholders') }}
                    </h5>
                </div>
                <div class="card-body">
                    @if($property->owners->count() > 0)
                        <div class="mb-3">
                            <strong>{{ __('estate::estate.role_owner') }}:</strong>
                            <ul class="list-unstyled mt-2">
                                @foreach($property->owners as $owner)
                                    <li>
                                        <i class="las la-user me-1"></i>
                                        {{ $owner->cname }}
                                        @if($owner->pivot->is_primary)
                                            <span class="badge bg-primary">{{ __('estate::estate.primary_owner') }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($property->tenants->count() > 0)
                        <div class="mb-3">
                            <strong>{{ __('estate::estate.role_tenant') }}:</strong>
                            <ul class="list-unstyled mt-2">
                                @foreach($property->tenants as $tenant)
                                    <li><i class="las la-user me-1"></i> {{ $tenant->cname }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($property->buyers->count() > 0)
                        <div class="mb-3">
                            <strong>{{ __('estate::estate.role_buyer') }}:</strong>
                            <ul class="list-unstyled mt-2">
                                @foreach($property->buyers as $buyer)
                                    <li><i class="las la-user me-1"></i> {{ $buyer->cname }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($property->agents->count() > 0)
                        <div class="mb-3">
                            <strong>{{ __('estate::estate.role_agent') }}:</strong>
                            <ul class="list-unstyled mt-2">
                                @foreach($property->agents as $agent)
                                    <li><i class="las la-user-tie me-1"></i> {{ $agent->name }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($property->stakeholders->count() === 0)
                        <p class="text-muted mb-0">{{ __('estate::estate.no_data_found') }}</p>
                    @endif
                </div>
            </div>

            {{-- Quick Stats --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="las la-chart-bar me-2"></i>
                        {{ __('estate::estate.quick_stats') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">{{ __('estate::estate.created_at') }}</small>
                        <p class="mb-0">{{ $property->created_at->format('Y-m-d H:i') }}</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">{{ __('estate::estate.updated_at') }}</small>
                        <p class="mb-0">{{ $property->updated_at->format('Y-m-d H:i') }}</p>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted">{{ __('estate::estate.total_images') }}</small>
                        <p class="mb-0">{{ $property->media->count() }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
