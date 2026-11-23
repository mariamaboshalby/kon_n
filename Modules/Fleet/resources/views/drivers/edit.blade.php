@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.fleet')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('fleet::fleet.drivers'),
        'breadcrumb_items' => [
            ['label' => __('fleet::fleet.Home'), 'url' => route('admin.dashboard')],
            ['label' => __('fleet::fleet.drivers'), 'url' => route('fleet.drivers.index')],
            ['label' => __('fleet::fleet.Edit')],
        ],
    ])

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('fleet::fleet.edit_driver') }}: {{ $driver->name }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('fleet.drivers.update', $driver->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">
                                    {{ __('fleet::fleet.driver_name') }} <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="name" id="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $driver->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">
                                    {{ __('fleet::fleet.phone') }} <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="phone" id="phone"
                                    class="form-control @error('phone') is-invalid @enderror"
                                    value="{{ old('phone', $driver->phone) }}" required>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="license_number" class="form-label">
                                    {{ __('fleet::fleet.license_number') }}
                                </label>
                                <input type="text" name="license_number" id="license_number"
                                    class="form-control @error('license_number') is-invalid @enderror"
                                    value="{{ old('license_number', $driver->license_number) }}">
                                @error('license_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="vehicle_type" class="form-label">
                                    {{ __('fleet::fleet.vehicle_type_label') }}
                                </label>
                                <input type="text" name="vehicle_type" id="vehicle_type"
                                    class="form-control @error('vehicle_type') is-invalid @enderror"
                                    value="{{ old('vehicle_type', $driver->vehicle_type) }}">
                                @error('vehicle_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="is_available" class="form-label">
                                    {{ __('fleet::fleet.Status') }}
                                </label>
                                <select name="is_available" id="is_available"
                                    class="form-control @error('is_available') is-invalid @enderror">
                                    <option value="1" {{ old('is_available', $driver->is_available ? '1' : '0') == '1' ? 'selected' : '' }}>
                                        {{ __('fleet::fleet.available') }}
                                    </option>
                                    <option value="0" {{ old('is_available', $driver->is_available ? '1' : '0') == '0' ? 'selected' : '' }}>
                                        {{ __('fleet::fleet.unavailable') }}
                                    </option>
                                </select>
                                @error('is_available')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <x-branches::branch-select :branches="$branches" :selected="$driver->branch_id" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="notes" class="form-label">{{ __('fleet::fleet.Notes') }}</label>
                                <textarea name="notes" id="notes" rows="3"
                                    class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $driver->notes) }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>{{ __('fleet::fleet.Update') }}
                            </button>
                            <a href="{{ route('fleet.drivers.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>{{ __('fleet::fleet.Back') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
