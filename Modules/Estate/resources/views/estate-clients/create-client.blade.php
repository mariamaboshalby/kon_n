@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.estate')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('estate::estate.add_new_client'),
        'breadcrumb_items' => [
            ['label' => __('common.dashboard'), 'url' => route('admin.dashboard')],
            ['label' => __('estate::estate.estate_clients'), 'url' => route('estate.clients.index')],
            ['label' => __('estate::estate.add_new_client')],
        ],
    ])

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="las la-user-plus me-2"></i>{{ __('estate::estate.add_new_client') }}
                    </h5>
                </div>
                <div class="card-body">

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('estate.clients.store') }}" method="POST">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">{{ __('estate::estate.client_name') }}</label>
                                <input type="text" name="cname" value="{{ old('cname') }}" required
                                       class="form-control @error('cname') is-invalid @enderror">
                                @error('cname')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('common.phone') }}</label>
                                <input type="text" name="phone" value="{{ old('phone') }}" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('common.email') }}</label>
                                <input type="email" name="email" value="{{ old('email') }}" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('common.phone') }} 2</label>
                                <input type="text" name="phone2" value="{{ old('phone2') }}" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('common.address') }}</label>
                                <input type="text" name="address" value="{{ old('address') }}" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('estate::estate.client_type') }}</label>
                                <select name="client_type_id" class="form-select">
                                    <option value="">{{ __('estate::estate.select') }}</option>
                                    @foreach($clientTypes as $type)
                                        <option value="{{ $type->id }}" {{ old('client_type_id') == $type->id ? 'selected' : '' }}>
                                            {{ $type->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('estate::estate.client_category') }}</label>
                                <select name="client_category_id" class="form-select">
                                    <option value="">{{ __('estate::estate.select') }}</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ old('client_category_id') == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('common.notes') }}</label>
                                <textarea name="info" rows="2" class="form-control">{{ old('info') }}</textarea>
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" value="1" id="is_active"
                                           class="form-check-input" {{ old('is_active', 1) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">{{ __('common.active') }}</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="las la-save me-1"></i>{{ __('common.save') }}
                            </button>
                            <a href="{{ route('estate.clients.index') }}" class="btn btn-secondary">
                                <i class="las la-times me-1"></i>{{ __('common.cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
