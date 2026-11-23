@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.estate')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('estate::estate.property_types'),
        'breadcrumb_items' => [
            ['label' => __('common.dashboard'), 'url' => route('admin.dashboard')],
            ['label' => __('estate::estate.property_types')],
        ],
    ])

    <div class="row">
        <div class="col-12">

            @if(session('message'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('message') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">{{ __('estate::estate.property_types_tree') }}</h5>
                @can('create PropertyTypes')
                    <a href="{{ route('property-types.create') }}" class="btn btn-primary btn-sm">
                        <i class="las la-plus me-1"></i> {{ __('estate::estate.add_property_type') }}
                    </a>
                @endcan
            </div>

            <div class="row g-3">
                @forelse($roots as $root)
                    <div class="col-md-6 col-xl-3">
                        <div class="card h-100 border-0 shadow-sm">
                            {{-- Root header --}}
                            <div class="card-header d-flex justify-content-between align-items-center py-2"
                                 style="background: linear-gradient(135deg,#34d3a3,#28a745); color:#fff;">
                                <span class="fw-bold">
                                    <i class="{{ $root->icon }} me-1"></i>
                                    {{ $root->name }}
                                </span>
                                <div class="d-flex gap-1">
                                    @can('create PropertyTypes')
                                        <a href="{{ route('property-types.create', ['parent_id' => $root->id]) }}"
                                           class="btn btn-sm btn-light py-0 px-1" title="{{ __('estate::estate.add_child_type') }}">
                                            <i class="las la-plus"></i>
                                        </a>
                                    @endcan
                                    @can('edit PropertyTypes')
                                        <a href="{{ route('property-types.edit', $root) }}"
                                           class="btn btn-sm btn-light py-0 px-1">
                                            <i class="las la-edit"></i>
                                        </a>
                                    @endcan
                                    @can('delete PropertyTypes')
                                        <form action="{{ route('property-types.destroy', $root) }}" method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light py-0 px-1"
                                                    onclick="return confirm('{{ __('common.confirm_delete') }}')">
                                                <i class="las la-trash text-danger"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </div>

                            {{-- Children list --}}
                            <ul class="list-group list-group-flush">
                                @forelse($root->activeChildren as $child)
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                        <span>
                                            <i class="{{ $child->icon }} me-1 text-muted"></i>
                                            {{ $child->name }}
                                            @unless($child->is_active)
                                                <span class="badge bg-secondary ms-1">{{ __('common.inactive') }}</span>
                                            @endunless
                                        </span>
                                        <div class="d-flex gap-1">
                                            @can('edit PropertyTypes')
                                                <a href="{{ route('property-types.edit', $child) }}"
                                                   class="btn btn-sm btn-outline-success py-0 px-1">
                                                    <i class="las la-edit"></i>
                                                </a>
                                            @endcan
                                            @can('delete PropertyTypes')
                                                <form action="{{ route('property-types.destroy', $child) }}" method="POST" class="d-inline">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1"
                                                            onclick="return confirm('{{ __('common.confirm_delete') }}')">
                                                        <i class="las la-trash"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </li>
                                @empty
                                    <li class="list-group-item text-muted small py-2">
                                        {{ __('estate::estate.no_children') }}
                                    </li>
                                @endforelse
                            </ul>

                            <div class="card-footer text-muted small py-1">
                                {{ $root->activeChildren->count() }} {{ __('estate::estate.sub_types') }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="las la-info-circle me-2"></i>
                            {{ __('estate::estate.no_property_types') }}
                        </div>
                    </div>
                @endforelse
            </div>

        </div>
    </div>
@endsection
