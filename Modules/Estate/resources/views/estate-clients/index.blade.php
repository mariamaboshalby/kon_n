@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.estate')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('estate::estate.estate_clients'),
        'breadcrumb_items' => [
            ['label' => __('common.dashboard'), 'url' => route('admin.dashboard')],
            ['label' => __('estate::estate.properties'), 'url' => route('properties.index')],
            ['label' => __('estate::estate.estate_clients')],
        ],
    ])

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="las la-users me-2"></i>{{ __('estate::estate.estate_clients') }}
            </h5>
            <span class="badge bg-primary">{{ $clients->total() }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('estate::estate.client_name') }}</th>
                            <th>{{ __('common.phone') }}</th>
                            <th class="text-center">{{ __('estate::estate.role_owner') }}</th>
                            <th class="text-center">{{ __('estate::estate.role_tenant') }}</th>
                            <th class="text-center">{{ __('estate::estate.role_buyer') }}</th>
                            <th class="text-center">{{ __('common.total') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clients as $client)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                             style="width:36px;height:36px;font-size:.9rem;flex-shrink:0">
                                            {{ mb_substr($client->cname, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold">{{ $client->cname }}</div>
                                            @if($client->email)
                                                <small class="text-muted">{{ $client->email }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $client->phone ?? '-' }}</td>
                                <td class="text-center">
                                    @if($client->owned_count)
                                        <span class="badge bg-success">{{ $client->owned_count }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($client->rented_count)
                                        <span class="badge bg-info">{{ $client->rented_count }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($client->bought_count)
                                        <span class="badge bg-warning text-dark">{{ $client->bought_count }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center fw-bold">
                                    {{ $client->owned_count + $client->rented_count + $client->bought_count }}
                                </td>
                                <td>
                                    <a href="{{ route('estate.clients.show', $client) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="las la-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="las la-users fs-2 d-block mb-2"></i>
                                    {{ __('estate::estate.no_estate_clients') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($clients->hasPages())
            <div class="card-footer">
                {{ $clients->links() }}
            </div>
        @endif
    </div>
@endsection
