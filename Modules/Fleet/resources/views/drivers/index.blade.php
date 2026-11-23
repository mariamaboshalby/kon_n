@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.fleet')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('fleet::fleet.drivers'),
        'breadcrumb_items' => [
            ['label' => __('fleet::fleet.Home'), 'url' => route('admin.dashboard')],
            ['label' => __('fleet::fleet.drivers')],
        ],
    ])

    <div class="row">
        <div class="col-lg-12">
            @can('create Fleet Drivers')
                <a href="{{ route('fleet.drivers.create') }}" type="button" class="btn btn-primary font-hold fw-bold">
                    {{ __('fleet::fleet.Add New') }}
                    <i class="fas fa-plus me-2"></i>
                </a>
            @endcan
            <br><br>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive" style="overflow-x: auto;">
                        <x-table-export-actions table-id="drivers-table" filename="drivers"
                            excel-label="{{ __('fleet::fleet.Export Excel') }}"
                            pdf-label="{{ __('fleet::fleet.Export PDF') }}"
                            print-label="{{ __('fleet::fleet.Print') }}" />

                        <table id="drivers-table" class="table table-striped mb-0" style="min-width: 1200px;">
                            <thead class="table-light text-center align-middle">
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('fleet::fleet.driver_name') }}</th>
                                    <th>{{ __('fleet::fleet.phone') }}</th>
                                    <th>{{ __('fleet::fleet.license_number') }}</th>
                                    <th>{{ __('fleet::fleet.vehicle_type_label') }}</th>
                                    <th>{{ __('fleet::fleet.assigned_vehicles') }}</th>
                                    <th>{{ __('fleet::fleet.Status') }}</th>
                                    @canany(['edit Fleet Drivers', 'delete Fleet Drivers'])
                                        <th>{{ __('fleet::fleet.Actions') }}</th>
                                    @endcanany
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($drivers as $driver)
                                    <tr class="text-center">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $driver->name }}</td>
                                        <td>{{ $driver->phone }}</td>
                                        <td>{{ $driver->license_number ?? '-' }}</td>
                                        <td>{{ $driver->vehicle_type ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-info">
                                                {{ $driver->vehicles_count ?? $driver->vehicles->count() }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($driver->is_available)
                                                <span class="badge bg-success">{{ __('fleet::fleet.available') }}</span>
                                            @else
                                                <span class="badge bg-danger">{{ __('fleet::fleet.unavailable') }}</span>
                                            @endif
                                        </td>
                                        @canany(['edit Fleet Drivers', 'delete Fleet Drivers'])
                                            <td>
                                                @can('view Fleet Drivers')
                                                    <a class="btn btn-primary btn-icon-square-sm"
                                                        href="{{ route('fleet.drivers.show', $driver->id) }}">
                                                        <i class="las la-eye"></i>
                                                    </a>
                                                @endcan
                                                @can('edit Fleet Drivers')
                                                    <a class="btn btn-success btn-icon-square-sm"
                                                        href="{{ route('fleet.drivers.edit', $driver->id) }}">
                                                        <i class="las la-edit"></i>
                                                    </a>
                                                @endcan
                                                @can('delete Fleet Drivers')
                                                    <form action="{{ route('fleet.drivers.destroy', $driver->id) }}"
                                                        method="POST" style="display:inline-block;"
                                                        onsubmit="return confirm('{{ __('fleet::fleet.confirm_delete_item') }}');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-icon-square-sm">
                                                            <i class="las la-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </td>
                                        @endcanany
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            <div class="alert alert-info py-3 mb-0">
                                                {{ __('fleet::fleet.no_data_available') }}
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="mt-3">
                            {{ $drivers->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
