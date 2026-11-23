@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.crm')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('crm::crm.ticket_types'),
        'breadcrumb_items' => [
            ['label' => __('crm::crm.dashboard'), 'url' => route('admin.dashboard')],
            ['label' => __('crm::crm.ticket_types')],
        ],
    ])
    <div class="row">
        <div class="col-lg-12">
            <div class="d-flex justify-content-end mb-3">
                @can('create Ticket Types')
                    <a href="{{ route('ticket-types.create') }}" class="btn btn-main font-hold fw-bold">
                        <i class="fas fa-plus me-2"></i> {{ __('crm::crm.add_new') }}
                    </a>
                @endcan
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <x-table-export-actions table-id="ticket-types-table" filename="ticket-types" :excel-label="__('crm::crm.export_excel')" :pdf-label="__('crm::crm.export_pdf')" :print-label="__('crm::crm.print')" />
                        <table id="ticket-types-table" class="table table-striped text-center align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <x-sortable-header field="title" :sortField="$sortField" :sortDirection="$sortDirection" :label="__('crm::crm.title')" />
                                    <x-sortable-header field="created_at" :sortField="$sortField" :sortDirection="$sortDirection" :label="__('crm::crm.created_at')" />
                                    @canany(['edit Ticket Types', 'delete Ticket Types'])
                                        <th>{{ __('crm::crm.actions') }}</th>
                                    @endcanany
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($ticketTypes as $type)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $type->title }}</td>
                                        <td>{{ $type->created_at->format('Y-m-d') }}</td>
                                        @canany(['edit Ticket Types', 'delete Ticket Types'])
                                            <td>
                                                @can('edit Ticket Types')
                                                    <a href="{{ route('ticket-types.edit', $type->id) }}" class="btn btn-success btn-icon-square-sm"><i class="las la-edit"></i></a>
                                                @endcan
                                                @can('delete Ticket Types')
                                                    <form action="{{ route('ticket-types.destroy', $type->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('{{ __('crm::crm.confirm_delete_ticket_type') }}');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-icon-square-sm"><i class="las la-trash"></i></button>
                                                    </form>
                                                @endcan
                                            </td>
                                        @endcanany
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            <div class="alert alert-info py-3 mb-0"><i class="las la-info-circle me-2"></i>{{ __('crm::crm.no_data_available') }}</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        @if($ticketTypes->hasPages())
                            <div class="mt-3 d-flex justify-content-center">{{ $ticketTypes->links() }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection