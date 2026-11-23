<div>
    @push('styles')
    <style>
        .prop-card { transition: transform .25s, box-shadow .25s; }
        .prop-card:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,0,0,.14) !important; }
        .prop-card:hover .prop-overlay { opacity: 1 !important; }
        .prop-card:hover .prop-img { transform: scale(1.05); }
        .prop-img { transition: transform .4s; }
    </style>
    @endpush

    @if(session('message'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0 fw-bold text-dark">{{ __('estate::estate.properties') }}</h6>
        @can('create Properties')
            <a href="{{ route('properties.create') }}" class="btn btn-sm btn-primary">
                <i class="las la-plus me-1"></i>{{ __('estate::estate.add_property') }}
            </a>
        @endcan
    </div>

    {{-- Filters --}}
    <div class="card mb-3 border-0 shadow-sm" style="border-radius:12px">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col">
                    <label class="form-label small mb-1 text-muted">{{ __('estate::estate.search') }}</label>
                    <input type="text" wire:model.live.debounce.500ms="search" class="form-control form-control-sm"
                           placeholder="{{ __('estate::estate.search_placeholder') }}">
                </div>
                <div class="col">
                    <label class="form-label small mb-1 text-muted">{{ __('estate::estate.city') }}</label>
                    <input type="text" wire:model.live.debounce.500ms="city" class="form-control form-control-sm"
                           placeholder="{{ __('estate::estate.city') }}">
                </div>
                <div class="col">
                    <label class="form-label small mb-1 text-muted">{{ __('estate::estate.listing_type') }}</label>
                    <select wire:model.live="listingType" class="form-select form-select-sm">
                        <option value="">{{ __('estate::estate.all') }}</option>
                        @foreach(\Modules\Estate\Enums\ListingType::cases() as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col">
                    <label class="form-label small mb-1 text-muted">{{ __('estate::estate.status') }}</label>
                    <select wire:model.live="status" class="form-select form-select-sm">
                        <option value="">{{ __('estate::estate.all') }}</option>
                        @foreach(\Modules\Estate\Enums\PropertyStatus::cases() as $s)
                            <option value="{{ $s->value }}">{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col">
                    <label class="form-label small mb-1 text-muted">{{ __('estate::estate.rooms_count') }}</label>
                    <input type="number" wire:model.live="rooms" class="form-control form-control-sm" min="0">
                </div>
                <div class="col">
                    <label class="form-label small mb-1 text-muted">{{ __('estate::estate.min_price') }}</label>
                    <input type="number" wire:model.live.debounce.500ms="minPrice" class="form-control form-control-sm" min="0" step="1000">
                </div>
                <div class="col">
                    <label class="form-label small mb-1 text-muted">{{ __('estate::estate.max_price') }}</label>
                    <input type="number" wire:model.live.debounce.500ms="maxPrice" class="form-control form-control-sm" min="0" step="1000">
                </div>
                <div class="col-auto">
                    <button wire:click="resetFilters" class="btn btn-sm btn-outline-secondary" title="{{ __('estate::estate.reset_filters') }}">
                        <i class="las la-redo"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Loading --}}
    <div wire:loading class="text-center py-2">
        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
    </div>

    {{-- Cards --}}
    @php
        $grads = [
            'linear-gradient(135deg,#1a1a2e 0%,#16213e 100%)',
            'linear-gradient(135deg,#0f3460 0%,#533483 100%)',
            'linear-gradient(135deg,#1b4332 0%,#2d6a4f 100%)',
            'linear-gradient(135deg,#370617 0%,#6a040f 100%)',
            'linear-gradient(135deg,#03045e 0%,#0077b6 100%)',
            'linear-gradient(135deg,#3d405b 0%,#81b29a 100%)',
        ];
    @endphp

    <div class="row g-3" wire:loading.class="opacity-50">
        @forelse($this->properties as $i => $property)
            @php
                $sc = match($property->status->value) {
                    'available' => ['bg' => '#00b894', 'text' => '#fff'],
                    'reserved'  => ['bg' => '#fdcb6e', 'text' => '#2d3436'],
                    default     => ['bg' => '#b2bec3', 'text' => '#2d3436'],
                };
                $img  = $property->getFirstMediaUrl('property_images');
                $grad = $grads[$i % count($grads)];
            @endphp
            <div class="col-sm-6 col-lg-4 col-xl-3">
                <div class="prop-card" style="border-radius:16px;overflow:hidden;background:#fff;box-shadow:0 4px 18px rgba(0,0,0,.08)">

                    {{-- Thumbnail --}}
                    <div class="position-relative overflow-hidden" style="height:175px">
                        @if($img)
                            <img src="{{ $img }}" alt="{{ $property->title }}" class="prop-img"
                                 style="width:100%;height:175px;object-fit:cover;display:block">
                        @else
                            <div class="prop-img d-flex align-items-center justify-content-center"
                                 style="height:175px;background:{{ $grad }}">
                                <i class="las la-building" style="font-size:3.5rem;color:rgba(255,255,255,.2)"></i>
                            </div>
                        @endif

                        {{-- Badges --}}
                        <div class="position-absolute top-0 start-0 m-2 d-flex gap-1">
                            <span class="badge fw-semibold"
                                  style="background:{{ $sc['bg'] }};color:{{ $sc['text'] }};border-radius:20px;font-size:.65rem">
                                {{ $property->status->label() }}
                            </span>
                        </div>

                        {{-- Hover overlay --}}
                        <div class="prop-overlay position-absolute top-0 start-0 end-0 bottom-0 d-flex align-items-center justify-content-center gap-3"
                             style="background:rgba(15,15,30,.6);opacity:0;transition:opacity .25s;backdrop-filter:blur(3px)">
                            @can('view Properties')
                                <a href="{{ route('properties.show', $property) }}"
                                   class="d-flex align-items-center justify-content-center"
                                   style="width:44px;height:44px;border-radius:50%;background:rgba(255,255,255,.15);border:2px solid rgba(255,255,255,.7);color:#fff;text-decoration:none;transition:background .2s"
                                   onmouseenter="this.style.background='rgba(255,255,255,.35)'"
                                   onmouseleave="this.style.background='rgba(255,255,255,.15)'"
                                   title="{{ __('common.view') }}">
                                    <i class="las la-eye" style="font-size:1.2rem"></i>
                                </a>
                            @endcan
                            @can('edit Properties')
                                <a href="{{ route('properties.edit', $property) }}"
                                   class="d-flex align-items-center justify-content-center"
                                   style="width:44px;height:44px;border-radius:50%;background:rgba(0,184,148,.2);border:2px solid #00b894;color:#00b894;text-decoration:none;transition:background .2s"
                                   onmouseenter="this.style.background='rgba(0,184,148,.5)';this.style.color='#fff'"
                                   onmouseleave="this.style.background='rgba(0,184,148,.2)';this.style.color='#00b894'"
                                   title="{{ __('common.edit') }}">
                                    <i class="las la-edit" style="font-size:1.2rem"></i>
                                </a>
                            @endcan
                            @can('delete Properties')
                                <button wire:click="delete({{ $property->id }})"
                                        wire:confirm="{{ __('estate::estate.confirm_delete_property') }}"
                                        class="d-flex align-items-center justify-content-center border-0"
                                        style="width:44px;height:44px;border-radius:50%;background:rgba(214,48,49,.2);border:2px solid #d63031 !important;color:#d63031;cursor:pointer;transition:background .2s"
                                        onmouseenter="this.style.background='rgba(214,48,49,.5)';this.style.color='#fff'"
                                        onmouseleave="this.style.background='rgba(214,48,49,.2)';this.style.color='#d63031'"
                                        title="{{ __('common.delete') }}">
                                    <i class="las la-trash" style="font-size:1.2rem"></i>
                                </button>
                            @endcan
                        </div>
                    </div>

                    {{-- Body --}}
                    <div class="p-3">
                        <div class="fw-bold text-dark mb-1"
                             style="font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"
                             title="{{ $property->title }}">
                            {{ $property->title }}
                        </div>

                        <div class="d-flex align-items-center gap-1 mb-2" style="font-size:.78rem;color:#636e72">
                            <i class="las la-map-marker" style="color:#00b894"></i>
                            {{ $property->city }}@if($property->district) · {{ $property->district }}@endif
                        </div>

                        <div class="d-flex gap-3 mb-3" style="font-size:.75rem;color:#636e72">
                            @if($property->area)
                                <span><i class="las la-vector-square me-1" style="color:#0984e3"></i>{{ number_format((float)$property->area, 0) }} {{ __('estate::estate.sqm') }}</span>
                            @endif
                            @if($property->rooms_count)
                                <span><i class="las la-bed me-1" style="color:#6c5ce7"></i>{{ $property->rooms_count }}</span>
                            @endif
                            @if($property->bathrooms_count)
                                <span><i class="las la-bath me-1" style="color:#00cec9"></i>{{ $property->bathrooms_count }}</span>
                            @endif
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-2" style="border-top:1px solid #f5f5f5">
                            <div>
                                <div style="font-size:.65rem;color:#b2bec3;text-transform:uppercase;letter-spacing:.5px">{{ __('estate::estate.price') }}</div>
                                <div class="fw-bold" style="font-size:1rem;color:#2d3436">
                                    {{ number_format((float)$property->price, 0) }}
                                    <small style="font-size:.7rem;color:#b2bec3;font-weight:400"> {{ __('estate::estate.currency') }}</small>
                                </div>
                            </div>
                            @if($property->propertyType)
                                <span style="background:#f8f9fa;color:#636e72;padding:3px 10px;border-radius:20px;font-size:.68rem;border:1px solid #e9ecef">
                                    {{ $property->propertyType->name }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5 text-muted">
                <i class="las la-building d-block mb-2" style="font-size:4rem;opacity:.15"></i>
                <p class="mb-0">{{ __('estate::estate.no_properties_found') }}</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($this->properties->hasPages())
        <div class="mt-4">{{ $this->properties->links() }}</div>
    @endif
</div>
