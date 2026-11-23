@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.estate')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('estate::estate.contacts'),
        'breadcrumb_items' => [
            ['label' => __('common.dashboard'), 'url' => route('admin.dashboard')],
            ['label' => __('estate::estate.estate_clients'), 'url' => route('estate.clients.index')],
            ['label' => __('estate::estate.contacts')],
        ],
    ])

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="las la-address-book me-2"></i>{{ __('estate::estate.contacts') }}
            </h5>
            <div class="d-flex gap-2">
                <span class="badge bg-primary">{{ $contacts->total() }}</span>
                @can('create Client Contacts')
                    <a href="{{ route('client-contacts.create') }}" class="btn btn-sm btn-primary">
                        <i class="las la-plus me-1"></i>{{ __('estate::estate.add_contact') }}
                    </a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('estate::estate.contact_name') }}</th>
                            <th>{{ __('estate::estate.client_name') }}</th>
                            <th>{{ __('common.phone') }}</th>
                            <th>{{ __('common.email') }}</th>
                            <th>{{ __('estate::estate.position') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contacts as $contact)
                            <tr>
                                <td class="fw-bold">{{ $contact->name }}</td>
                                <td>
                                    <a href="{{ route('estate.clients.show', $contact->client) }}" class="text-decoration-none">
                                        {{ $contact->client->cname }}
                                    </a>
                                </td>
                                <td>{{ $contact->phone ?? '-' }}</td>
                                <td>{{ $contact->email ?? '-' }}</td>
                                <td>{{ $contact->position ?? '-' }}</td>
                                <td>
                                    @can('edit Client Contacts')
                                        <a href="{{ route('client-contacts.edit', $contact) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="las la-edit"></i>
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    {{ __('estate::estate.no_contacts') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($contacts->hasPages())
            <div class="card-footer">{{ $contacts->links() }}</div>
        @endif
    </div>
@endsection
