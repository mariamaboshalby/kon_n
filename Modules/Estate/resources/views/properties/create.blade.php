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
        'title' => __('estate::estate.add_property'),
        'breadcrumb_items' => [
            ['label' => __('common.dashboard'), 'url' => route('admin.dashboard')],
            ['label' => __('estate::estate.properties'), 'url' => route('properties.index')],
            ['label' => __('estate::estate.add_property')],
        ],
    ])

    <div class="row">
        <div class="col-12">

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
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
                        <i class="las la-building me-2"></i>{{ __('estate::estate.add_property') }}
                    </h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('properties.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        {{-- ══════════════════════════════════════════════════════
                             SECTION 1 : Basic Information
                        ══════════════════════════════════════════════════════ --}}
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="las la-info-circle me-1"></i>{{ __('estate::estate.basic_information') }}
                        </h5>
                        <div class="row mb-4">

                            <div class="col-md-6 mb-3">
                                <label class="form-label required">{{ __('estate::estate.title') }}</label>
                                <input type="text" name="title" value="{{ old('title') }}" required
                                       class="form-control @error('title') is-invalid @enderror">
                                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label required">{{ __('estate::estate.sku') }}</label>
                                <input type="text" name="sku" value="{{ old('sku', 'PROP-' . strtoupper(uniqid())) }}" required
                                       class="form-control @error('sku') is-invalid @enderror">
                                @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Category (root types) --}}
                            <div class="col-md-3 mb-3">
                                <label class="form-label required">{{ __('estate::estate.property_category') }}</label>
                                <select id="sel_category" class="form-select">
                                    <option value="">{{ __('estate::estate.select') }}</option>
                                    @foreach(\Modules\Estate\Models\PropertyType::roots()->active()->with('activeChildren')->orderBy('sort_order')->get() as $root)
                                        <option value="{{ $root->id }}"
                                            data-children="{{ $root->activeChildren->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'attrs' => $c->attributes ?? []])->toJson() }}">
                                            {{ $root->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Sub-type (children) --}}
                            <div class="col-md-3 mb-3">
                                <label class="form-label required">{{ __('estate::estate.property_sub_type') }}</label>
                                <select name="property_type_id" id="sel_subtype"
                                        class="form-select @error('property_type_id') is-invalid @enderror" required>
                                    <option value="">{{ __('estate::estate.select_category_first') }}</option>
                                </select>
                                @error('property_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label required">{{ __('estate::estate.listing_type') }}</label>
                                <select name="listing_type" required
                                        class="form-select @error('listing_type') is-invalid @enderror">
                                    <option value="">{{ __('estate::estate.select') }}</option>
                                    @foreach(\Modules\Estate\Enums\ListingType::cases() as $t)
                                        <option value="{{ $t->value }}" {{ old('listing_type') == $t->value ? 'selected' : '' }}>
                                            {{ $t->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('listing_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label required">{{ __('estate::estate.status') }}</label>
                                <select name="status" required
                                        class="form-select @error('status') is-invalid @enderror">
                                    <option value="">{{ __('estate::estate.select') }}</option>
                                    @foreach(\Modules\Estate\Enums\PropertyStatus::cases() as $s)
                                        <option value="{{ $s->value }}" {{ old('status', 'available') == $s->value ? 'selected' : '' }}>
                                            {{ $s->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                        </div>

                        {{-- ══════════════════════════════════════════════════════
                             SECTION 2 : Location
                        ══════════════════════════════════════════════════════ --}}
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="las la-map-marker me-1"></i>{{ __('estate::estate.location') }}
                        </h5>
                        <div class="row mb-4">

                            <div class="col-md-6 mb-3">
                                <label class="form-label required">{{ __('estate::estate.city') }}</label>
                                <input type="text" name="city" value="{{ old('city') }}" required
                                       class="form-control @error('city') is-invalid @enderror">
                                @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label required">{{ __('estate::estate.district') }}</label>
                                <input type="text" name="district" value="{{ old('district') }}" required
                                       class="form-control @error('district') is-invalid @enderror">
                                @error('district')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-8 mb-3">
                                <label class="form-label">{{ __('estate::estate.address_details') }}</label>
                                <textarea name="address_details" rows="2" class="form-control">{{ old('address_details') }}</textarea>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('estate::estate.landmark') }}</label>
                                <input type="text" name="landmark" value="{{ old('landmark') }}" class="form-control">
                            </div>

                            {{-- Hidden coords --}}
                            <input type="hidden" name="latitude"  id="lat_input" value="{{ old('latitude') }}">
                            <input type="hidden" name="longitude" id="lng_input" value="{{ old('longitude') }}">

                            <div class="col-12">
                                <label class="form-label">
                                    <i class="las la-map me-1"></i>{{ __('estate::estate.pick_location') }}
                                    <small class="text-muted ms-1">{{ __('estate::estate.map_hint') }}</small>
                                </label>
                                <div id="property-map" style="height:350px;border-radius:8px;border:1px solid #dee2e6;"></div>
                                <div class="mt-1 d-flex align-items-center gap-3">
                                    <small class="text-muted" id="coords-display">{{ __('estate::estate.no_location_selected') }}</small>
                                    <button type="button" id="btn-clear-map" class="btn btn-sm btn-outline-secondary">
                                        <i class="las la-times"></i> {{ __('estate::estate.clear_location') }}
                                    </button>
                                </div>
                            </div>

                        </div>

                        {{-- ══════════════════════════════════════════════════════
                             SECTION 3 : Specifications (dynamic per type)
                        ══════════════════════════════════════════════════════ --}}
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="las la-ruler-combined me-1"></i>{{ __('estate::estate.specifications') }}
                        </h5>
                        <div class="row mb-4">

                            {{-- Always shown --}}
                            <div class="col-md-3 mb-3">
                                <label class="form-label required">{{ __('estate::estate.area') }}</label>
                                <input type="number" name="area" step="0.01" min="0" value="{{ old('area') }}" required
                                       class="form-control @error('area') is-invalid @enderror">
                                @error('area')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Shown by JS based on type attributes --}}
                            <div class="col-md-3 mb-3 attr-field attr-rooms_count" style="display:none">
                                <label class="form-label">{{ __('estate::estate.rooms_count') }}</label>
                                <input type="number" name="rooms_count" step="1" min="0" value="{{ old('rooms_count') }}" class="form-control">
                            </div>

                            <div class="col-md-3 mb-3 attr-field attr-bathrooms_count" style="display:none">
                                <label class="form-label">{{ __('estate::estate.bathrooms_count') }}</label>
                                <input type="number" name="bathrooms_count" step="1" min="0" value="{{ old('bathrooms_count') }}" class="form-control">
                            </div>

                            <div class="col-md-3 mb-3 attr-field attr-floor_number" style="display:none">
                                <label class="form-label">{{ __('estate::estate.floor_number') }}</label>
                                <input type="number" name="floor_number" step="1" min="0" value="{{ old('floor_number') }}" class="form-control">
                            </div>

                            <div class="col-md-3 mb-3 attr-field attr-finishing_level" style="display:none">
                                <label class="form-label">{{ __('estate::estate.finishing_level') }}</label>
                                <select name="finishing_level" class="form-select">
                                    <option value="">{{ __('estate::estate.select') }}</option>
                                    @foreach(\Modules\Estate\Enums\FinishingLevel::cases() as $fl)
                                        <option value="{{ $fl->value }}" {{ old('finishing_level') == $fl->value ? 'selected' : '' }}>
                                            {{ $fl->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 mb-3 attr-field attr-build_year" style="display:none">
                                <label class="form-label">{{ __('estate::estate.build_year') }}</label>
                                <input type="number" name="build_year" step="1" min="1900" max="{{ date('Y') + 5 }}" value="{{ old('build_year') }}" class="form-control">
                            </div>

                            <div class="col-md-3 mb-3 attr-field attr-roof_area" style="display:none">
                                <label class="form-label">{{ __('estate::estate.roof_area') }}</label>
                                <input type="number" name="roof_area" step="0.01" min="0" value="{{ old('roof_area') }}" class="form-control">
                            </div>

                            <div class="col-md-3 mb-3 attr-field attr-garden_area" style="display:none">
                                <label class="form-label">{{ __('estate::estate.garden_area') }}</label>
                                <input type="number" name="garden_area" step="0.01" min="0" value="{{ old('garden_area') }}" class="form-control">
                            </div>

                            <div class="col-md-3 mb-3 attr-field attr-facade_width" style="display:none">
                                <label class="form-label">{{ __('estate::estate.facade_width') }}</label>
                                <input type="number" name="facade_width" step="0.01" min="0" value="{{ old('facade_width') }}" class="form-control">
                            </div>

                            <div class="col-md-3 mb-3 attr-field attr-is_corner" style="display:none">
                                <label class="form-label d-block">&nbsp;</label>
                                <div class="form-check mt-2">
                                    <input type="checkbox" name="is_corner" value="1" id="is_corner" class="form-check-input" {{ old('is_corner') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_corner">{{ __('estate::estate.is_corner') }}</label>
                                </div>
                            </div>

                            <div class="col-md-3 mb-3 attr-field attr-land_license_type" style="display:none">
                                <label class="form-label">{{ __('estate::estate.land_license_type') }}</label>
                                <input type="text" name="land_license_type" value="{{ old('land_license_type') }}" class="form-control">
                            </div>

                            <div class="col-md-3 mb-3 attr-field attr-street_width" style="display:none">
                                <label class="form-label">{{ __('estate::estate.street_width') }}</label>
                                <input type="number" name="street_width" step="0.01" min="0" value="{{ old('street_width') }}" class="form-control">
                            </div>

                            <div class="col-md-3 mb-3 attr-field attr-land_shape" style="display:none">
                                <label class="form-label">{{ __('estate::estate.land_shape') }}</label>
                                <input type="text" name="land_shape" value="{{ old('land_shape') }}" class="form-control">
                            </div>

                            <div class="col-md-3 mb-3 attr-field attr-furnishing_percentage" style="display:none">
                                <label class="form-label">{{ __('estate::estate.furnishing_percentage') }}</label>
                                <input type="number" name="furnishing_percentage" step="1" min="0" max="100" value="{{ old('furnishing_percentage', 0) }}" class="form-control">
                            </div>

                            <div class="col-md-3 mb-3 attr-field attr-is_furnished" style="display:none">
                                <label class="form-label d-block">&nbsp;</label>
                                <div class="form-check mt-2">
                                    <input type="checkbox" name="is_furnished" value="1" id="is_furnished" class="form-check-input" {{ old('is_furnished') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_furnished">{{ __('estate::estate.is_furnished') }}</label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3 attr-field attr-neighbor_notes" style="display:none">
                                <label class="form-label">{{ __('estate::estate.neighbor_notes') }}</label>
                                <textarea name="neighbor_notes" rows="3" class="form-control">{{ old('neighbor_notes') }}</textarea>
                            </div>

                            <div class="col-md-6 mb-3 attr-field attr-reputation_notes" style="display:none">
                                <label class="form-label">{{ __('estate::estate.reputation_notes') }}</label>
                                <textarea name="reputation_notes" rows="3" class="form-control">{{ old('reputation_notes') }}</textarea>
                            </div>

                        </div>

                        {{-- ══════════════════════════════════════════════════════
                             SECTION 4 : Financial
                        ══════════════════════════════════════════════════════ --}}
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="las la-money-bill me-1"></i>{{ __('estate::estate.financial_information') }}
                        </h5>
                        <div class="row mb-4">

                            <div class="col-md-4 mb-3">
                                <label class="form-label required">{{ __('estate::estate.price') }}</label>
                                <input type="number" name="price" step="0.01" min="0" value="{{ old('price') }}" required
                                       class="form-control @error('price') is-invalid @enderror">
                                @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label required">{{ __('estate::estate.payment_method') }}</label>
                                <select name="payment_method" id="sel_payment" required
                                        class="form-select @error('payment_method') is-invalid @enderror">
                                    <option value="">{{ __('estate::estate.select') }}</option>
                                    @foreach(\Modules\Estate\Enums\PaymentMethod::cases() as $pm)
                                        <option value="{{ $pm->value }}" {{ old('payment_method') == $pm->value ? 'selected' : '' }}>
                                            {{ $pm->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('payment_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4 mb-3" id="div_installment" style="display:none">
                                <label class="form-label">{{ __('estate::estate.installment_period_months') }}</label>
                                <input type="number" name="installment_period_months" step="1" min="1"
                                       value="{{ old('installment_period_months') }}"
                                       class="form-control @error('installment_period_months') is-invalid @enderror">
                                @error('installment_period_months')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('estate::estate.commission') }}</label>
                                <input type="number" name="commission" step="0.01" min="0" value="{{ old('commission') }}" class="form-control">
                            </div>

                        </div>

                        {{-- ══════════════════════════════════════════════════════
                             SECTION 5 : Stakeholders
                        ══════════════════════════════════════════════════════ --}}
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="las la-users me-1"></i>{{ __('estate::estate.stakeholders') }}
                        </h5>
                        <div class="row mb-4">

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('estate::estate.role_owner') }}</label>
                                <select id="sel_owners" name="owners[]" multiple>
                                    @foreach(\App\Models\Client::orderBy('cname')->get() as $client)
                                        <option value="{{ $client->id }}" {{ in_array($client->id, old('owners', [])) ? 'selected' : '' }}>
                                            {{ $client->cname }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('estate::estate.role_agent') }}</label>
                                <select id="sel_agents" name="agents[]" multiple>
                                    @foreach(\Modules\HR\Models\Employee::orderBy('name')->get() as $emp)
                                        <option value="{{ $emp->id }}" {{ in_array($emp->id, old('agents', [])) ? 'selected' : '' }}>
                                            {{ $emp->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                        </div>

                        {{-- ══════════════════════════════════════════════════════
                             SECTION 6 : Images
                        ══════════════════════════════════════════════════════ --}}
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="las la-images me-1"></i>{{ __('estate::estate.images') }}
                        </h5>
                        <div class="row mb-4">
                            <div class="col-12">
                                <input type="file" name="images[]" multiple accept="image/*" class="form-control">
                                <small class="text-muted">{{ __('estate::estate.max_file_size') }}: 5MB</small>
                            </div>
                        </div>

                        {{-- ══════════════════════════════════════════════════════
                             Actions
                        ══════════════════════════════════════════════════════ --}}
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="las la-save me-1"></i>{{ __('common.save') }}
                            </button>
                            <a href="{{ route('properties.index') }}" class="btn btn-secondary">
                                <i class="las la-times me-1"></i>{{ __('common.cancel') }}
                            </a>
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
document.addEventListener('DOMContentLoaded', function () {

    /* ── Leaflet Map ─────────────────────────────────────────────────────── */
    var latEl   = document.getElementById('lat_input');
    var lngEl   = document.getElementById('lng_input');
    var display = document.getElementById('coords-display');
    var initLat = parseFloat(latEl.value) || 24.7136;
    var initLng = parseFloat(lngEl.value) || 46.6753;
    var hasCoords = latEl.value !== '';

    var map = L.map('property-map').setView([initLat, initLng], hasCoords ? 15 : 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors', maxZoom: 19
    }).addTo(map);

    var marker = null;

    function placeMarker(lat, lng) {
        if (marker) map.removeLayer(marker);
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        latEl.value = lat.toFixed(8);
        lngEl.value = lng.toFixed(8);
        display.textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
        marker.on('dragend', function (e) {
            var p = e.target.getLatLng();
            placeMarker(p.lat, p.lng);
        });
    }

    if (hasCoords) placeMarker(initLat, initLng);

    map.on('click', function (e) { placeMarker(e.latlng.lat, e.latlng.lng); });

    document.getElementById('btn-clear-map').addEventListener('click', function () {
        if (marker) { map.removeLayer(marker); marker = null; }
        latEl.value = '';
        lngEl.value = '';
        display.textContent = '{{ __("estate::estate.no_location_selected") }}';
    });

    setTimeout(function () { map.invalidateSize(); }, 300);

    /* ── Category → Sub-type ─────────────────────────────────────────────── */
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

        /* hide all dynamic fields */
        document.querySelectorAll('.attr-field').forEach(function (el) {
            el.style.display = 'none';
        });
    });

    typeSel.addEventListener('change', function () {
        var opt   = this.options[this.selectedIndex];
        var attrs = opt ? JSON.parse(opt.dataset.attrs || '[]') : [];

        document.querySelectorAll('.attr-field').forEach(function (el) {
            el.style.display = 'none';
        });
        attrs.forEach(function (attr) {
            document.querySelectorAll('.attr-' + attr).forEach(function (el) {
                el.style.display = '';
            });
        });
    });

    /* ── Payment method → installment field ─────────────────────────────── */
    var paymentSel   = document.getElementById('sel_payment');
    var installDiv   = document.getElementById('div_installment');

    function toggleInstallment() {
        var v = paymentSel.value;
        installDiv.style.display = (v === 'installments' || v === 'both') ? '' : 'none';
    }
    paymentSel.addEventListener('change', toggleInstallment);
    toggleInstallment();

    /* ── Select2 ─────────────────────────────────────────────────────────── */
    var s2opts = function (placeholder) {
        return { theme: 'bootstrap-5', placeholder: placeholder, allowClear: true, width: '100%', language: '{{ app()->getLocale() }}' };
    };
    $('#sel_owners').select2(s2opts('{{ __("estate::estate.search_clients") }}'));
    $('#sel_agents').select2(s2opts('{{ __("estate::estate.search_employees") }}'));

});
</script>
@endsection
