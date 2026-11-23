@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.fleet')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('fleet::fleet.driver'),
        'breadcrumb_items' => [
            ['label' => __('fleet::fleet.Home'), 'url' => route('admin.dashboard')],
            ['label' => __('fleet::fleet.drivers'), 'url' => route('fleet.drivers.index')],
            ['label' => $driver->name],
        ],
    ])

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="page-title">{{ __('fleet::fleet.driver') }}: {{ $driver->name }}</h4>
                        <div class="d-flex gap-2">
                            @can('edit Fleet Drivers')
                                <a href="{{ route('fleet.drivers.edit', $driver->id) }}" class="btn btn-primary">
                                    <i class="las la-edit"></i> {{ __('fleet::fleet.Edit') }}
                                </a>
                            @endcan
                            <a href="{{ route('fleet.drivers.index') }}" class="btn btn-secondary">
                                <i class="las la-arrow-right"></i> {{ __('fleet::fleet.Back') }}
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">{{ __('fleet::fleet.driver_name') }}:</label>
                            <div class="form-control-static">{{ $driver->name }}</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">{{ __('fleet::fleet.phone') }}:</label>
                            <div class="form-control-static">{{ $driver->phone }}</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">{{ __('fleet::fleet.license_number') }}:</label>
                            <div class="form-control-static">{{ $driver->license_number ?? '-' }}</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">{{ __('fleet::fleet.vehicle_type_label') }}:</label>
                            <div class="form-control-static">{{ $driver->vehicle_type ?? '-' }}</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">{{ __('fleet::fleet.Status') }}:</label>
                            <div class="form-control-static">
                                @if ($driver->is_available)
                                    <span class="badge bg-success">{{ __('fleet::fleet.available') }}</span>
                                @else
                                    <span class="badge bg-danger">{{ __('fleet::fleet.unavailable') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">{{ __('fleet::fleet.Branch') }}:</label>
                            <div class="form-control-static">{{ $driver->branch->name ?? '-' }}</div>
                        </div>

                        @if ($driver->notes)
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">{{ __('fleet::fleet.Notes') }}:</label>
                                <div class="form-control-static">{{ $driver->notes }}</div>
                            </div>
                        @endif
                    </div>

                    {{-- Assigned Vehicles --}}
                    @if ($driver->vehicles->count() > 0)
                        <hr>
                        <h5 class="mb-3">
                            <i class="las la-truck me-1"></i>{{ __('fleet::fleet.assigned_vehicles') }}
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('fleet::fleet.Code') }}</th>
                                        <th>{{ __('fleet::fleet.Plate Number') }}</th>
                                        <th>{{ __('fleet::fleet.Name') }}</th>
                                        <th>{{ __('fleet::fleet.Vehicle Type') }}</th>
                                        <th>{{ __('fleet::fleet.Status') }}</th>
                                        <th>{{ __('fleet::fleet.Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($driver->vehicles as $vehicle)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $vehicle->code }}</td>
                                            <td>{{ $vehicle->plate_number }}</td>
                                            <td>{{ $vehicle->name }}</td>
                                            <td>{{ $vehicle->vehicleType->name ?? '-' }}</td>
                                            <td>
                                                <span class="badge bg-{{ $vehicle->status->color() }}">
                                                    {{ $vehicle->status->label() }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('fleet.vehicles.show', $vehicle->id) }}"
                                                    class="btn btn-primary btn-icon-square-sm">
                                                    <i class="las la-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    {{-- Recent Trips --}}
                    @if ($driver->trips->count() > 0)
                        <hr>
                        <h5 class="mb-3">
                            <i class="las la-route me-1"></i>{{ __('fleet::fleet.recent_trips') }}
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('fleet::fleet.Trip Number') }}</th>
                                        <th>{{ __('fleet::fleet.Vehicle') }}</th>
                                        <th>{{ __('fleet::fleet.Start Location') }}</th>
                                        <th>{{ __('fleet::fleet.End Location') }}</th>
                                        <th>{{ __('fleet::fleet.Status') }}</th>
                                        <th>{{ __('fleet::fleet.Start Date') }}</th>
                                        <th>{{ __('fleet::fleet.Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($driver->trips as $trip)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $trip->trip_number }}</td>
                                            <td>{{ $trip->vehicle->name ?? '-' }}</td>
                                            <td>{{ $trip->start_location }}</td>
                                            <td>{{ $trip->end_location }}</td>
                                            <td>
                                                <span class="badge bg-{{ $trip->status->color() }}">
                                                    {{ $trip->status->label() }}
                                                </span>
                                            </td>
                                            <td>{{ $trip->start_date->format('Y-m-d') }}</td>
                                            <td>
                                                <a href="{{ route('fleet.trips.show', $trip->id) }}"
                                                    class="btn btn-primary btn-icon-square-sm">
                                                    <i class="las la-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
