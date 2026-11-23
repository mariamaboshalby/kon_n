@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.estate')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => $client->cname,
        'breadcrumb_items' => [
            ['label' => __('common.dashboard'), 'url' => route('admin.dashboard')],
            ['label' => __('estate::estate.estate_clients'), 'url' => route('estate.clients.index')],
            ['label' => $client->cname],
        ],
    ])

    <div class="row">

        {{-- ══ Left: Client Card ══════════════════════════════════════════════ --}}
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mx-auto bg-primary text-white d-flex align-items-center justify-content-center mb-3"
                         style="width:72px;height:72px;border-radius:50%;font-size:1.8rem;">
                        {{ mb_substr($client->cname, 0, 1) }}
                    </div>
                    <h5 class="fw-bold mb-0">{{ $client->cname }}</h5>
                    <small class="text-muted">{{ $client->clientType->title ?? '-' }}</small>

                    <div class="d-grid gap-2 mt-3">
                        @if($client->phone)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $client->phone) }}"
                               target="_blank" class="btn btn-success btn-sm">
                                <i class="lab la-whatsapp me-1"></i>{{ $client->phone }}
                            </a>
                        @endif
                        @if($client->email)
                            <a href="mailto:{{ $client->email }}" class="btn btn-outline-info btn-sm">
                                <i class="las la-envelope me-1"></i>{{ $client->email }}
                            </a>
                        @endif
                        <a href="{{ route('clients.show', $client) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                            <i class="las la-external-link-alt me-1"></i>{{ __('estate::estate.view_in_crm') }}
                        </a>
                        @can('edit Clients')
                            <a href="{{ route('clients.edit', $client) }}" class="btn btn-outline-primary btn-sm">
                                <i class="las la-edit me-1"></i>{{ __('common.edit') }}
                            </a>
                        @endcan
                    </div>

                    {{-- Stats --}}
                    @php
                        $owned  = $properties->where('pivot.role', 'owner')->count();
                        $rented = $properties->where('pivot.role', 'tenant')->count();
                        $bought = $properties->where('pivot.role', 'buyer')->count();
                    @endphp
                    <div class="row g-2 mt-3 text-center">
                        <div class="col-4">
                            <div class="border rounded p-2">
                                <div class="fw-bold text-success">{{ $owned }}</div>
                                <small class="text-muted" style="font-size:.7rem">{{ __('estate::estate.role_owner') }}</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2">
                                <div class="fw-bold text-info">{{ $rented }}</div>
                                <small class="text-muted" style="font-size:.7rem">{{ __('estate::estate.role_tenant') }}</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2">
                                <div class="fw-bold text-warning">{{ $bought }}</div>
                                <small class="text-muted" style="font-size:.7rem">{{ __('estate::estate.role_buyer') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Add Client Button --}}
            @can('create Clients')
                <a href="{{ route('clients.create') }}" class="btn btn-primary w-100 mt-3">
                    <i class="las la-plus me-1"></i>{{ __('estate::estate.add_new_client') }}
                </a>
            @endcan
        </div>

        {{-- ══ Right: Tabs ════════════════════════════════════════════════════ --}}
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header bg-white">
                    <ul class="nav nav-tabs card-header-tabs" id="clientTabs">
                        <li class="nav-item">
                            <a class="nav-link active fw-bold" data-bs-toggle="tab" href="#tab-properties">
                                <i class="las la-building me-1"></i>
                                {{ __('estate::estate.properties') }}
                                <span class="badge bg-primary ms-1">{{ $properties->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-bold" data-bs-toggle="tab" href="#tab-contacts">
                                <i class="las la-address-book me-1"></i>
                                {{ __('estate::estate.contacts') }}
                                <span class="badge bg-secondary ms-1">{{ $contacts->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-bold" data-bs-toggle="tab" href="#tab-leads">
                                <i class="las la-funnel-dollar me-1"></i>
                                {{ __('estate::estate.leads') }}
                                <span class="badge bg-secondary ms-1">{{ $leads->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-bold" data-bs-toggle="tab" href="#tab-info">
                                <i class="las la-user me-1"></i>
                                {{ __('estate::estate.client_info') }}
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body tab-content p-0">

                    {{-- ── Properties Tab ──────────────────────────────────── --}}
                    <div class="tab-pane fade show active p-3" id="tab-properties">
                        @forelse($properties as $property)
                            @php
                                $role = $property->pivot->role;
                                $roleColor = match($role) { 'owner' => 'success', 'tenant' => 'info', 'buyer' => 'warning', default => 'secondary' };
                            @endphp
                            <div class="d-flex justify-content-between align-items-start border-bottom py-3">
                                <div class="d-flex gap-3">
                                    <i class="las la-building text-muted" style="font-size:2rem;line-height:1"></i>
                                    <div>
                                        <div class="fw-bold">
                                            {{ $property->title }}
                                            <span class="badge bg-{{ $roleColor }} ms-1">{{ __('estate::estate.role_' . $role) }}</span>
                                            @if($property->pivot->is_primary)
                                                <span class="badge bg-dark ms-1">{{ __('estate::estate.primary_owner') }}</span>
                                            @endif
                                        </div>
                                        <small class="text-muted">
                                            <code>{{ $property->sku }}</code>
                                            &bull; {{ $property->city }}, {{ $property->district }}
                                            @if($property->propertyType) &bull; {{ $property->propertyType->name }} @endif
                                        </small>
                                        <div class="mt-1 d-flex align-items-center gap-2">
                                            <span class="badge bg-{{ $property->status->value === 'available' ? 'success' : ($property->status->value === 'reserved' ? 'warning' : 'secondary') }}">
                                                {{ $property->status->label() }}
                                            </span>
                                            <span class="badge bg-light text-dark border">{{ $property->listing_type->label() }}</span>
                                            <span class="text-primary fw-bold small">
                                                {{ number_format((float) $property->price, 0) }} {{ __('estate::estate.currency') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <a href="{{ route('properties.show', $property) }}" class="btn btn-sm btn-outline-primary flex-shrink-0">
                                    <i class="las la-eye"></i>
                                </a>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted">
                                <i class="las la-building d-block mb-2" style="font-size:3rem"></i>
                                {{ __('estate::estate.no_properties_found') }}
                            </div>
                        @endforelse
                    </div>

                    {{-- ── Contacts Tab ─────────────────────────────────────── --}}
                    <div class="tab-pane fade" id="tab-contacts">
                        <div class="p-3 border-bottom d-flex justify-content-end">
                            @can('create Client Contacts')
                                <a href="{{ route('client-contacts.create', ['client_id' => $client->id]) }}"
                                   class="btn btn-sm btn-primary">
                                    <i class="las la-plus me-1"></i>{{ __('estate::estate.add_contact') }}
                                </a>
                            @endcan
                        </div>
                        @forelse($contacts as $contact)
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                <div>
                                    <div class="fw-bold">{{ $contact->name }}</div>
                                    <small class="text-muted">
                                        @if($contact->phone) <i class="las la-phone me-1"></i>{{ $contact->phone }} @endif
                                        @if($contact->email) &bull; <i class="las la-envelope me-1"></i>{{ $contact->email }} @endif
                                        @if($contact->position) &bull; {{ $contact->position }} @endif
                                    </small>
                                </div>
                                @can('edit Client Contacts')
                                    <a href="{{ route('client-contacts.edit', $contact) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="las la-edit"></i>
                                    </a>
                                @endcan
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted">
                                <i class="las la-address-book d-block mb-2" style="font-size:3rem"></i>
                                {{ __('estate::estate.no_contacts') }}
                            </div>
                        @endforelse
                    </div>

                    {{-- ── Leads Tab ────────────────────────────────────────── --}}
                    <div class="tab-pane fade" id="tab-leads">
                        <div class="p-3 border-bottom d-flex justify-content-end">
                            @can('create Leads')
                                <a href="{{ route('leads.board', ['client_id' => $client->id]) }}"
                                   class="btn btn-sm btn-primary">
                                    <i class="las la-plus me-1"></i>{{ __('estate::estate.add_lead') }}
                                </a>
                            @endcan
                        </div>
                        @forelse($leads as $lead)
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                <div>
                                    <div class="fw-bold">{{ $lead->title }}</div>
                                    <small class="text-muted">
                                        @if($lead->status)
                                            <span class="badge bg-info">{{ $lead->status->name }}</span>
                                        @endif
                                        @if($lead->amount)
                                            &bull; <span class="text-primary">{{ number_format((float)$lead->amount, 0) }}</span>
                                        @endif
                                        @if($lead->assignedTo)
                                            &bull; <i class="las la-user me-1"></i>{{ $lead->assignedTo->name }}
                                        @endif
                                    </small>
                                </div>
                                <small class="text-muted">{{ $lead->created_at->diffForHumans() }}</small>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted">
                                <i class="las la-funnel-dollar d-block mb-2" style="font-size:3rem"></i>
                                {{ __('estate::estate.no_leads') }}
                            </div>
                        @endforelse
                    </div>

                    {{-- ── Info Tab ─────────────────────────────────────────── --}}
                    <div class="tab-pane fade p-3" id="tab-info">
                        <table class="table table-bordered table-sm">
                            <tbody>
                                <tr><th width="35%">{{ __('estate::estate.client_name') }}</th><td>{{ $client->cname }}</td></tr>
                                <tr><th>{{ __('common.phone') }}</th><td>{{ $client->phone ?? '-' }}</td></tr>
                                <tr><th>{{ __('common.email') }}</th><td>{{ $client->email ?? '-' }}</td></tr>
                                <tr><th>{{ __('common.address') }}</th><td>{{ $client->address ?? '-' }}</td></tr>
                                <tr><th>{{ __('crm::crm.client_type') }}</th><td>{{ $client->clientType->title ?? '-' }}</td></tr>
                                <tr><th>{{ __('common.notes') }}</th><td>{{ $client->info ?? '-' }}</td></tr>
                                <tr><th>{{ __('common.created_at') }}</th><td>{{ $client->created_at->format('Y-m-d') }}</td></tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
