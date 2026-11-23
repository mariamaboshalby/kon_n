@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.estate')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('estate::estate.leads'),
        'breadcrumb_items' => [
            ['label' => __('common.dashboard'), 'url' => route('admin.dashboard')],
            ['label' => __('estate::estate.estate_clients'), 'url' => route('estate.clients.index')],
            ['label' => __('estate::estate.leads')],
        ],
    ])

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="las la-funnel-dollar me-2"></i>{{ __('estate::estate.leads') }}
            </h5>
            <div class="d-flex gap-2">
                <span class="badge bg-primary">{{ $leads->total() }}</span>
                @can('view Leads')
                    <a href="{{ route('leads.board') }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                        <i class="las la-external-link-alt me-1"></i>{{ __('estate::estate.view_leads_board') }}
                    </a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('estate::estate.lead_title') }}</th>
                            <th>{{ __('estate::estate.client_name') }}</th>
                            <th>{{ __('estate::estate.lead_status') }}</th>
                            <th>{{ __('estate::estate.lead_amount') }}</th>
                            <th>{{ __('estate::estate.assigned_to') }}</th>
                            <th>{{ __('common.created_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leads as $lead)
                            <tr>
                                <td class="fw-bold">{{ $lead->title }}</td>
                                <td>
                                    <a href="{{ route('estate.clients.show', $lead->client) }}" class="text-decoration-none">
                                        {{ $lead->client->cname }}
                                    </a>
                                </td>
                                <td>
                                    @if($lead->status)
                                        <span class="badge bg-info">{{ $lead->status->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($lead->amount)
                                        <span class="text-primary fw-bold">{{ number_format((float)$lead->amount, 0) }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $lead->assignedTo->name ?? '-' }}</td>
                                <td>{{ $lead->created_at->format('Y-m-d') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    {{ __('estate::estate.no_leads') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($leads->hasPages())
            <div class="card-footer">{{ $leads->links() }}</div>
        @endif
    </div>
@endsection
