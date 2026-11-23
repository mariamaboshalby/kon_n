@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.crm')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => $client->cname,
        'breadcrumb_items' => [
            ['label' => __('Clients'), 'url' => route('clients.index')],
        ],
    ])

    <div class="row">
        <!-- Sidebar: Contact Info & Quick Actions -->
        <div class="col-lg-3 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="avatar-circle mx-auto bg-light text-primary d-flex align-items-center justify-content-center"
                            style="width: 80px; height: 80px; font-size: 2rem; border-radius: 50%;">
                            {{ substr($client->cname, 0, 1) }}
                        </div>
                    </div>
                    <h5 class="card-title fw-bold">{{ $client->cname }}</h5>
                    <p class="text-muted small">{{ $client->clientType->title ?? __('Uncategorized') }}</p>

                    <div class="d-grid gap-2 mb-4">
                        @if ($client->phone)
                            @php
                                $cleanPhone = preg_replace('/[^0-9]/', '', $client->phone);
                                if (str_starts_with($cleanPhone, '0')) {
                                    $cleanPhone = '966' . substr($cleanPhone, 1);
                                }
                            @endphp
                            <a href="https://wa.me/{{ $cleanPhone }}" target="_blank" class="btn btn-success">
                                <i class="lab la-whatsapp"></i> {{ __('WhatsApp') }}
                            </a>
                            <a href="tel:+{{ $cleanPhone }}" class="btn btn-outline-primary">
                                <i class="las la-phone"></i> {{ __('Call') }}
                            </a>
                        @endif
                        @if ($client->email)
                            <a href="https://mail.google.com/mail/?view=cm&to={{ $client->email }}" target="_blank" class="btn btn-outline-info">
                                <i class="las la-envelope"></i> {{ __('Email') }}
                            </a>
                        @endif
                        <a href="{{ route('activities.create', ['client_id' => $client->id]) }}" class="btn btn-primary">
                            <i class="las la-plus-circle"></i> {{ __('Log Activity') }}
                        </a>
                    </div>

                    <div class="text-start">
                        <h6 class="text-muted text-uppercase small fw-bold mb-3">{{ __('Contact Details') }}</h6>
                        <ul class="list-unstyled mb-0">
                            @if ($client->phone)
                                <li class="mb-2"><i class="las la-phone me-2 text-muted"></i> {{ $client->phone }}</li>
                            @endif
                            @if ($client->email)
                                <li class="mb-2 d-flex align-items-center" style="max-width:100%; overflow:hidden;">
                                    <i class="las la-envelope me-2 text-muted flex-shrink-0"></i>
                                    <span class="text-truncate" title="{{ $client->email }}">{{ $client->email }}</span>
                                </li>
                            @endif
                            @if ($client->address)
                                <li class="mb-2"><i class="las la-map-marker me-2 text-muted"></i> {{ $client->address }}
                                </li>
                            @endif
                            @if ($client->national_id)
                                <li class="mb-2"><i class="las la-id-card me-2 text-muted"></i>
                                    {{ $client->national_id }}</li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content: Timeline & Tabs -->
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom-0 pt-3">
                    <ul class="nav nav-tabs card-header-tabs" id="clientTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active fw-bold" id="timeline-tab" data-bs-toggle="tab" href="#timeline"
                                role="tab">
                                <i class="las la-stream me-1"></i> {{ __('Timeline') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-bold" id="profile-tab" data-bs-toggle="tab" href="#profile"
                                role="tab">
                                <i class="las la-user me-1"></i> {{ __('Full Profile') }}
                            </a>
                        </li>
                        @can('view Properties')
                        <li class="nav-item">
                            <a class="nav-link fw-bold" id="properties-tab" data-bs-toggle="tab" href="#properties"
                                role="tab">
                                <i class="las la-building me-1"></i> {{ __('estate::estate.properties') }}
                                @php $propCount = $client->properties()->count(); @endphp
                                @if($propCount)
                                    <span class="badge bg-primary ms-1">{{ $propCount }}</span>
                                @endif
                            </a>
                        </li>
                        @endcan
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="clientTabsContent">

                        <!-- TIMELINE TAB -->
                        <div class="tab-pane fade show active" id="timeline" role="tabpanel">
                            <!-- Quick Add Box (Optional for future) -->
                            <!-- <div class="mb-4">...Input to add note...</div> -->

                            <div class="timeline-container px-2">
                                @if ($timeline && $timeline->count() > 0)
                                    @php $lastDate = null; @endphp
                                    @foreach ($timeline as $item)
                                        @php
                                            $currentDate = \Carbon\Carbon::parse($item->date)->format('Y-m-d');
                                            $isNewDay = $currentDate != $lastDate;
                                            $lastDate = $currentDate;
                                        @endphp

                                        @if ($isNewDay)
                                            <div class="timeline-date-label mb-3 mt-4">
                                                <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
                                                    {{ \Carbon\Carbon::parse($item->date)->translatedFormat('l, d F Y') }}
                                                </span>
                                            </div>
                                        @endif

                                        <div class="timeline-item d-flex gap-3 mb-4">
                                            <div class="timeline-icon">
                                                <div class="icon-circle bg-soft-{{ $item->color ?? 'secondary' }} text-{{ $item->color ?? 'secondary' }} d-flex align-items-center justify-content-center"
                                                    style="width: 40px; height: 40px; border-radius: 50%;">
                                                    <i class="{{ $item->icon ?? 'las la-circle' }} fs-5"></i>
                                                </div>
                                            </div>
                                            <div class="timeline-content flex-grow-1 card p-3 border-0 bg-light">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <h6 class="mb-0 fw-bold text-dark">{{ $item->title }}</h6>
                                                    <small
                                                        class="text-muted">{{ \Carbon\Carbon::parse($item->date)->format('H:i A') }}</small>
                                                </div>
                                                <p class="mb-1 text-muted small">{{ $item->description }}</p>
                                                @if (isset($item->link))
                                                    <div class="mt-2">
                                                        <a href="{{ $item->link }}"
                                                            class="btn btn-sm btn-outline-{{ $item->color ?? 'secondary' }} rounded-pill px-3">
                                                            {{ __('View Details') }} <i
                                                                class="las la-arrow-right ms-1"></i>
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-center py-5">
                                        <div class="mb-3">
                                            <i class="las la-history text-muted" style="font-size: 4rem; opacity: 0.5;"></i>
                                        </div>
                                        <h5 class="text-muted">{{ __('No activity found') }}</h5>
                                        <p class="text-muted">{{ __('This client has no recorded history yet.') }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- PROPERTIES TAB -->
                        @can('view Properties')
                        <div class="tab-pane fade" id="properties" role="tabpanel">
                            @php $clientProperties = $client->properties()->with('propertyType')->withPivot(['role','is_primary'])->latest()->get(); @endphp
                            @forelse($clientProperties as $prop)
                                @php
                                    $role = $prop->pivot->role;
                                    $roleColor = match($role) { 'owner' => 'success', 'tenant' => 'info', 'buyer' => 'warning', default => 'secondary' };
                                @endphp
                                <div class="d-flex justify-content-between align-items-start border-bottom py-3">
                                    <div>
                                        <div class="fw-bold">
                                            {{ $prop->title }}
                                            <span class="badge bg-{{ $roleColor }} ms-1">{{ __('estate::estate.role_' . $role) }}</span>
                                        </div>
                                        <small class="text-muted">
                                            <code>{{ $prop->sku }}</code> &bull; {{ $prop->city }}, {{ $prop->district }}
                                        </small>
                                        <div class="mt-1">
                                            <span class="badge bg-light text-dark border">{{ $prop->listing_type->label() }}</span>
                                            <span class="ms-2 text-primary fw-bold">{{ number_format((float)$prop->price, 0) }} {{ __('estate::estate.currency') }}</span>
                                        </div>
                                    </div>
                                    <a href="{{ route('properties.show', $prop) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="las la-eye"></i>
                                    </a>
                                </div>
                            @empty
                                <div class="text-center py-4 text-muted">
                                    <i class="las la-building fs-2 d-block mb-2"></i>
                                    {{ __('estate::estate.no_properties_found') }}
                                </div>
                            @endforelse
                        </div>
                        @endcan

                        <!-- PROFILE TAB -->
                        <div class="tab-pane fade" id="profile" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <tbody>
                                        <tr>
                                            <th width="30%">{{ __('Client Name') }}</th>
                                            <td>{{ $client->cname }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('Phone') }}</th>
                                            <td>{{ $client->phone ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('Email') }}</th>
                                            <td>{{ $client->email ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('Address') }}</th>
                                            <td>{{ $client->address ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('Client Type') }}</th>
                                            <td>{{ $client->clientType->title ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('Assigned User') }}</th>
                                            <td>{{ $client->assignedUser->name ?? __('Not Assigned') }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('Category') }}</th>
                                            <td>{{ $client->category->name ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('National ID') }}</th>
                                            <td>{{ $client->national_id ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('Created At') }}</th>
                                            <td>{{ $client->created_at->format('Y-m-d') }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('Notes') }}</th>
                                            <td>{{ $client->info ?? '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3 text-end">
                                <a href="{{ route('clients.edit', $client->id) }}" class="btn btn-primary">
                                    <i class="las la-edit"></i> {{ __('Edit Profile') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .timeline-container {
            position: relative;
        }

        .timeline-item {
            position: relative;
            z-index: 1;
        }

        /* Vertical line connecting items */
        .timeline-container::before {
            content: '';
            position: absolute;
            left: 28px;
            /* Center with the 40px icon + padding */
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #e9ecef;
            z-index: 0;
        }

        .timeline-date-label {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .bg-soft-primary {
            background-color: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }

        .bg-soft-success {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
        }

        .bg-soft-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .bg-soft-warning {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .bg-soft-info {
            background-color: rgba(13, 202, 240, 0.1);
            color: #0dcaf0;
        }

        .bg-soft-secondary {
            background-color: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }
    </style>
@endsection
