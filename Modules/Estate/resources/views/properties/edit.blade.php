@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.estate')
@endsection

@section('content')
    @push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <style>
        .form-label.required::after { content: ' *'; color: #dc3545; font-weight: bold; }
    </style>
    @endpush
    @include('components.breadcrumb', [
        'title' => __('estate::estate.edit_property'),
        'breadcrumb_items' => [
            ['label' => __('estate::estate.dashboard'), 'url' => route('admin.dashboard')],
            ['label' => __('estate::estate.properties'), 'url' => route('properties.index')],
            ['label' => __('estate::estate.edit_property')],
        ],
    ])

    <div class="row">
        <div class="col-lg-12">

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('store_error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <strong>خطأ:</strong> {{ session('store_error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="las la-edit me-2"></i>
                        {{ __('estate::estate.edit_property') }}: {{ $property->title }}
                    </h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('properties.update', $property) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        {{-- Basic Information --}}
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="las la-info-circle me-2"></i>
                                    {{ __('estate::estate.basic_information') }}
                                </h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label required">{{ __('estate::estate.title') }}</label>
                                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" 
                                       value="{{ old('title', $property->title) }}" required>
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label required">{{ __('estate::estate.sku') }}</label>
                                <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror" 
                                       value="{{ old('sku', $property->sku) }}" required>
                                @error('sku')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Category (roots) --}}
                            @php
                                $currentTypeId = old('property_type_id', $property->property_type_id);
                                $currentType   = $currentTypeId ? \Modules\Estate\Models\PropertyType::find($currentTypeId) : null;
                                $currentParent = $currentType?->parent;
                            @endphp

                            <div class="col-md-3 mb-3">
                                <label class="form-label required">{{ __('estate::estate.property_category') }}</label>
                                <select id="sel_category" class="form-select">
                                    <option value="">{{ __('estate::estate.select') }}</option>
                                    @foreach(\Modules\Estate\Models\PropertyType::roots()->active()->with('activeChildren')->orderBy('sort_order')->get() as $root)
                                        <option value="{{ $root->id }}"
                                            data-children="{{ $root->activeChildren->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'attrs' => $c->attributes ?? []])->toJson() }}"
                                            {{ ($currentParent?->id ?? $currentType?->id) == $root->id ? 'selected' : '' }}>
                                            {{ $root->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label required">{{ __('estate::estate.property_sub_type') }}</label>
                                <select name="property_type_id" id="sel_subtype"
                                        class="form-select @error('property_type_id') is-invalid @enderror" required>
                                    <option value="">{{ __('estate::estate.select_category_first') }}</option>
                                    @if($currentParent && $currentType)
                                        @foreach($currentParent->activeChildren as $child)
                                            <option value="{{ $child->id }}"
                                                    data-attrs="{{ json_encode($child->attributes ?? []) }}"
                                                    {{ $child->id == $currentTypeId ? 'selected' : '' }}>
                                                {{ $child->name }}
                                            </option>
                                        @endforeach
                                    @elseif($currentType && $currentType->isRoot())
                                        {{-- type is a root itself, show its children --}}
                                        @foreach($currentType->activeChildren as $child)
                                            <option value="{{ $child->id }}"
                                                    data-attrs="{{ json_encode($child->attributes ?? []) }}">
                                                {{ $child->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('property_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label required">{{ __('estate::estate.listing_type') }}</label>
                                <select name="listing_type" class="form-select @error('listing_type') is-invalid @enderror" required>
                                    <option value="">{{ __('estate::estate.select') }}</option>
                                    @foreach(\Modules\Estate\Enums\ListingType::cases() as $type)
                                        <option value="{{ $type->value }}" 
                                            {{ old('listing_type', $property->listing_type->value) == $type->value ? 'selected' : '' }}>
                                            {{ $type->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('listing_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label required">{{ __('estate::estate.status') }}</label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="">{{ __('estate::estate.select') }}</option>
                                    @foreach(\Modules\Estate\Enums\PropertyStatus::cases() as $status)
                                        <option value="{{ $status->value }}" 
                                            {{ old('status', $property->status->value) == $status->value ? 'selected' : '' }}>
                                            {{ $status->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('estate::estate.finishing_level') }}</label>
                                <select name="finishing_level" class="form-select @error('finishing_level') is-invalid @enderror">
                                    <option value="">{{ __('estate::estate.select') }}</option>
                                    @foreach(\Modules\Estate\Enums\FinishingLevel::cases() as $level)
                                        <option value="{{ $level->value }}" 
                                            {{ old('finishing_level', $property->finishing_level?->value) == $level->value ? 'selected' : '' }}>
                                            {{ $level->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('finishing_level')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Location Information --}}
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="las la-map-marker me-2"></i>
                                    {{ __('estate::estate.location') }}
                                </h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label required">{{ __('estate::estate.city') }}</label>
                                <input type="text" name="city" class="form-control @error('city') is-invalid @enderror" 
                                       value="{{ old('city', $property->city) }}" required>
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label required">{{ __('estate::estate.district') }}</label>
                                <input type="text" name="district" class="form-control @error('district') is-invalid @enderror" 
                                       value="{{ old('district', $property->district) }}" required>
                                @error('district')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-8 mb-3">
                                <label class="form-label">{{ __('estate::estate.address_details') }}</label>
                                <textarea name="address_details" rows="2" class="form-control">{{ old('address_details', $property->address_details) }}</textarea>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('estate::estate.landmark') }}</label>
                                <input type="text" name="landmark" class="form-control"
                                       value="{{ old('landmark', $property->landmark) }}">
                            </div>

                            {{-- Hidden lat/lng --}}
                            <input type="hidden" name="latitude"  id="lat_input"  value="{{ old('latitude', $property->latitude) }}">
                            <input type="hidden" name="longitude" id="lng_input"  value="{{ old('longitude', $property->longitude) }}">

                            {{-- Map --}}
                            <div class="col-12 mb-2">
                                <label class="form-label">
                                    <i class="las la-map me-1"></i>
                                    {{ __('estate::estate.pick_location') }}
                                    <small class="text-muted ms-2">{{ __('estate::estate.map_hint') }}</small>
                                </label>
                                <div id="property-map" style="height: 350px; border-radius: 8px; border: 1px solid #dee2e6; z-index: 1;"></div>
                                <div class="mt-1 d-flex gap-3 align-items-center">
                                    <small class="text-muted" id="coords-display">
                                        @if($property->latitude && $property->longitude)
                                            {{ $property->latitude }}, {{ $property->longitude }}
                                        @else
                                            {{ __('estate::estate.no_location_selected') }}
                                        @endif
                                    </small>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-location">
                                        <i class="las la-times"></i> {{ __('estate::estate.clear_location') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Property Specifications --}}
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="las la-ruler-combined me-2"></i>
                                    {{ __('estate::estate.specifications') }}
                                </h5>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label required">{{ __('estate::estate.area') }}</label>
                                <input type="number" step="0.01" name="area" class="form-control @error('area') is-invalid @enderror" 
                                       value="{{ old('area', $property->area) }}" required>
                                @error('area')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label required">{{ __('estate::estate.rooms_count') }}</label>
                                <input type="number" name="rooms_count" class="form-control @error('rooms_count') is-invalid @enderror" 
                                       value="{{ old('rooms_count', $property->rooms_count) }}" min="0" required>
                                @error('rooms_count')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label required">{{ __('estate::estate.bathrooms_count') }}</label>
                                <input type="number" name="bathrooms_count" class="form-control @error('bathrooms_count') is-invalid @enderror" 
                                       value="{{ old('bathrooms_count', $property->bathrooms_count) }}" min="0" required>
                                @error('bathrooms_count')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('estate::estate.floor_number') }}</label>
                                <input type="number" name="floor_number" class="form-control @error('floor_number') is-invalid @enderror" 
                                       value="{{ old('floor_number', $property->floor_number) }}" min="0">
                                @error('floor_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('estate::estate.build_year') }}</label>
                                <input type="number" name="build_year" class="form-control @error('build_year') is-invalid @enderror" 
                                       value="{{ old('build_year', $property->build_year) }}" min="1900" max="{{ date('Y') + 5 }}">
                                @error('build_year')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('estate::estate.furnishing_percentage') }}</label>
                                <input type="number" name="furnishing_percentage" class="form-control @error('furnishing_percentage') is-invalid @enderror" 
                                       value="{{ old('furnishing_percentage', $property->furnishing_percentage) }}" min="0" max="100">
                                @error('furnishing_percentage')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="is_furnished" value="1" class="form-check-input" id="is_furnished"
                                           {{ old('is_furnished', $property->is_furnished) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_furnished">
                                        {{ __('estate::estate.is_furnished') }}
                                    </label>
                                </div>
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

                            <div class="col-md-4 mb-3">
                                <label class="form-label required">{{ __('estate::estate.price') }}</label>
                                <input type="number" step="0.01" name="price" class="form-control @error('price') is-invalid @enderror" 
                                       value="{{ old('price', $property->price) }}" required>
                                @error('price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label required">{{ __('estate::estate.payment_method') }}</label>
                                <select name="payment_method" id="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                                    <option value="">{{ __('estate::estate.select') }}</option>
                                    @foreach(\Modules\Estate\Enums\PaymentMethod::cases() as $method)
                                        <option value="{{ $method->value }}" 
                                            {{ old('payment_method', $property->payment_method->value) == $method->value ? 'selected' : '' }}>
                                            {{ $method->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('payment_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3" id="installment_period_wrapper" style="display: none;">
                                <label class="form-label">{{ __('estate::estate.installment_period_months') }}</label>
                                <input type="number" name="installment_period_months" step="1"
                                       class="form-control @error('installment_period_months') is-invalid @enderror"
                                       value="{{ old('installment_period_months', $property->installment_period_months ? (int) $property->installment_period_months : '') }}" min="1">
                                @error('installment_period_months')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('estate::estate.commission') }}</label>
                                <input type="number" step="0.01" name="commission" class="form-control @error('commission') is-invalid @enderror" 
                                       value="{{ old('commission', $property->commission) }}" min="0">
                                @error('commission')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Stakeholders --}}
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="las la-users me-2"></i>
                                    {{ __('estate::estate.stakeholders') }}
                                </h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('estate::estate.role_owner') }}</label>
                                <select id="owners-select" name="owners[]" multiple>
                                    @foreach(\App\Models\Client::orderBy('cname')->get() as $client)
                                        <option value="{{ $client->id }}"
                                            {{ in_array($client->id, old('owners', $property->owners->pluck('id')->toArray())) ? 'selected' : '' }}>
                                            {{ $client->cname }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('owners')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('estate::estate.role_agent') }}</label>
                                <select id="agents-select" name="agents[]" multiple>
                                    @foreach(\Modules\HR\Models\Employee::orderBy('name')->get() as $employee)
                                        <option value="{{ $employee->id }}"
                                            {{ in_array($employee->id, old('agents', $property->agents->pluck('id')->toArray())) ? 'selected' : '' }}>
                                            {{ $employee->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('agents')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Additional Notes --}}
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="las la-sticky-note me-2"></i>
                                    {{ __('estate::estate.notes') }}
                                </h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('estate::estate.neighbor_notes') }}</label>
                                <textarea name="neighbor_notes" rows="3" class="form-control @error('neighbor_notes') is-invalid @enderror">{{ old('neighbor_notes', $property->neighbor_notes) }}</textarea>
                                @error('neighbor_notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('estate::estate.reputation_notes') }}</label>
                                <textarea name="reputation_notes" rows="3" class="form-control @error('reputation_notes') is-invalid @enderror">{{ old('reputation_notes', $property->reputation_notes) }}</textarea>
                                @error('reputation_notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Current Images --}}
                        @if($property->media->count() > 0)
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="las la-images me-2"></i>
                                    {{ __('estate::estate.current_images') }}
                                </h5>
                            </div>
                            <div class="col-12">
                                <div class="row g-3">
                                    @foreach($property->media as $media)
                                        <div class="col-md-3" id="media-{{ $media->id }}">
                                            <div class="card">
                                                <img src="{{ $media->getUrl() }}" class="card-img-top" alt="{{ $property->title }}">
                                                <div class="card-body p-2">
                                                    <button type="button" class="btn btn-danger btn-sm w-100"
                                                            onclick="deleteImage({{ $media->id }}, {{ $property->id }}, this)">
                                                        <i class="las la-trash"></i> {{ __('estate::estate.delete') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- New Images --}}
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="las la-upload me-2"></i>
                                    {{ __('estate::estate.add_new_images') }}
                                </h5>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ __('estate::estate.images') }}</label>
                                <input type="file" name="images[]" class="form-control @error('images') is-invalid @enderror" 
                                       multiple accept="image/*">
                                <small class="text-muted">{{ __('estate::estate.max_file_size') }}: 5MB</small>
                                @error('images')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Form Actions --}}
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="las la-save me-2"></i>
                                        {{ __('estate::estate.update') }}
                                    </button>
                                    <a href="{{ route('properties.index') }}" class="btn btn-secondary">
                                        <i class="las la-times me-2"></i>
                                        {{ __('estate::estate.cancel') }}
                                    </a>
                                    <a href="{{ route('properties.show', $property) }}" class="btn btn-info">
                                        <i class="las la-eye me-2"></i>
                                        {{ __('estate::estate.view') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // ─── Map ──────────────────────────────────────────────────────────────────
    const latInput      = document.getElementById('lat_input');
    const lngInput      = document.getElementById('lng_input');
    const coordsDisplay = document.getElementById('coords-display');

    const initLat = parseFloat(latInput.value) || 24.7136;
    const initLng = parseFloat(lngInput.value) || 46.6753;
    const hasInit = latInput.value !== '';

    const map = L.map('property-map').setView([initLat, initLng], hasInit ? 15 : 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19
    }).addTo(map);

    let marker = null;

    function setMarker(lat, lng) {
        if (marker) map.removeLayer(marker);
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        latInput.value  = lat.toFixed(8);
        lngInput.value  = lng.toFixed(8);
        coordsDisplay.textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        marker.on('dragend', function(e) {
            const pos = e.target.getLatLng();
            setMarker(pos.lat, pos.lng);
        });
    }

    if (hasInit) setMarker(initLat, initLng);

    map.on('click', function(e) {
        setMarker(e.latlng.lat, e.latlng.lng);
    });

    document.getElementById('clear-location').addEventListener('click', function() {
        if (marker) { map.removeLayer(marker); marker = null; }
        latInput.value  = '';
        lngInput.value  = '';
        coordsDisplay.textContent = '{{ __("estate::estate.no_location_selected") }}';
    });

    document.addEventListener('DOMContentLoaded', function() {
        // ── Category → Sub-type ──────────────────────────────────────────
        var catSel  = document.getElementById('sel_category');
        var typeSel = document.getElementById('sel_subtype');

        catSel.addEventListener('change', function () {
            var opt      = this.options[this.selectedIndex];
            var children = opt ? JSON.parse(opt.dataset.children || '[]') : [];

            typeSel.innerHTML = '<option value="">{{ __("estate::estate.select") }}</option>';
            children.forEach(function (c) {
                var o = document.createElement('option');
                o.value = c.id;
                o.textContent = c.name;
                o.dataset.attrs = JSON.stringify(c.attrs || []);
                typeSel.appendChild(o);
            });

            document.querySelectorAll('.attr-field').forEach(function (el) { el.style.display = 'none'; });
        });

        typeSel.addEventListener('change', function () {
            var opt   = this.options[this.selectedIndex];
            var attrs = opt ? JSON.parse(opt.dataset.attrs || '[]') : [];
            document.querySelectorAll('.attr-field').forEach(function (el) { el.style.display = 'none'; });
            attrs.forEach(function (attr) {
                document.querySelectorAll('.attr-' + attr).forEach(function (el) { el.style.display = ''; });
            });
        });

        // Trigger on load to show attrs for current type
        if (typeSel.value) {
            var evt = new Event('change');
            typeSel.dispatchEvent(evt);
        }

        // ── Payment method ────────────────────────────────────────────────
        const paymentMethodSelect = document.getElementById('payment_method');
        const installmentPeriodWrapper = document.getElementById('installment_period_wrapper');

        function toggleInstallmentPeriod() {
            const value = paymentMethodSelect.value;
            installmentPeriodWrapper.style.display = (value === 'installments' || value === 'both') ? 'block' : 'none';
        }

        paymentMethodSelect.addEventListener('change', toggleInstallmentPeriod);
        toggleInstallmentPeriod();

        $('#owners-select').select2({ theme: 'bootstrap-5', placeholder: '{{ __("estate::estate.search_clients") }}', allowClear: true, width: '100%', language: '{{ app()->getLocale() }}' });
        $('#agents-select').select2({ theme: 'bootstrap-5', placeholder: '{{ __("estate::estate.search_employees") }}', allowClear: true, width: '100%', language: '{{ app()->getLocale() }}' });

        setTimeout(() => map.invalidateSize(), 300);
    });

    function deleteImage(mediaId, propertyId, btn) {
        if (!confirm('{{ __("common.confirm_delete") }}')) return;

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        fetch('/estate/properties/' + propertyId + '/media/' + mediaId, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('media-' + mediaId).remove();
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="las la-trash"></i> {{ __("estate::estate.delete") }}';
                alert('حدث خطأ');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="las la-trash"></i> {{ __("estate::estate.delete") }}';
        });
    }
</script>
@endsection
