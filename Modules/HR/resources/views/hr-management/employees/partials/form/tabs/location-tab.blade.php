@php $p = $p ?? []; @endphp
<div>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-gradient-info text-white py-2">
            <h6 class="card-title mb-0 font-hold fw-bold">
                <i class="fas fa-map-marker-alt me-2"></i>{{ __('hr::hr.location') }}
            </h6>
        </div>
        <div class="card-body py-3">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">{{ __('hr::hr.country') ?? __('البلد') }}</label>
                    <select name="country_id" id="hrEmpCountry" class="form-select">
                        <option value="">{{ __('اختر البلد') }}</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected((string) old('country_id', $p['country_id'] ?? '') === (string) $country->id)>
                                {{ $country->title }}
                            </option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">{{ __('hr::hr.select_state') }}</label>
                    <select name="state_id" id="hrEmpState" class="form-select">
                        <option value="">{{ __('hr::hr.select_state') }}</option>
                        @foreach ($states as $state)
                            <option value="{{ $state->id }}"
                                    data-country="{{ $state->country_id }}"
                                    @selected((string) old('state_id', $p['state_id'] ?? '') === (string) $state->id)>
                                {{ $state->title }}
                            </option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">{{ __('المدينة') }}</label>
                    <select name="city_id" id="hrEmpCity" class="form-select">
                        <option value="">{{ __('اختر المدينة') }}</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}"
                                    data-state="{{ $city->state_id }}"
                                    @selected((string) old('city_id', $p['city_id'] ?? '') === (string) $city->id)>
                                {{ $city->title }}
                            </option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">{{ __('المنطقة') }}</label>
                    <select name="town_id" id="hrEmpTown" class="form-select">
                        <option value="">{{ __('اختر المنطقة') }}</option>
                        @foreach ($towns as $town)
                            <option value="{{ $town->id }}"
                                    data-city="{{ $town->city_id }}"
                                    @selected((string) old('town_id', $p['town_id'] ?? '') === (string) $town->id)>
                                {{ $town->title }}
                            </option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback"></div>
                </div>
            </div>
        </div>
    </div>
</div>
