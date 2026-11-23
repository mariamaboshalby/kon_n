@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.crm')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('crm::crm.return_types'),
        'breadcrumb_items' => [
            ['label' => __('crm::crm.dashboard'), 'url' => route('admin.dashboard')],
            ['label' => __('crm::crm.return_types')],
        ],
    ])
    <div class="row">
        <div class="col-lg-12">
            <div class="d-flex justify-content-end mb-3">
                @can('create Return Types')
                    <a href="{{ route('return-types.create') }}" class="btn btn-main font-hold fw-bold">
                        <i class="fas fa-plus me-2"></i> {{ __('crm::crm.add_new') }}
                    </a>
                @endcan
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <x-table-export-actions table-id="return-types-table" filename="return-types" :excel-label="__('crm::crm.export_excel')" :pdf-label="__('crm::crm.export_pdf')" :print-label="__('crm::crm.print')" />
                        <table id="return-types-table" class="table table-striped text-center align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <x-sortable-header field="title" :sortField="$sortField" :sortDirection="$sortDirection" :label="__('crm::crm.title')" />
                                    <x-sortable-header field="created_at" :sortField="$sortField" :sortDirection="$sortDirection" :label="__('crm::crm.created_at')" />
                                    @canany(['edit Return Types', 'delete Return Types'])
                                        <th>{{ __('crm::crm.actions') }}</th>
                                    @endcanany
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($returnTypes as $type)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $type->title }}</td>
                                        <td>{{ $type->created_at->format('Y-m-d') }}</td>
                                        @canany(['edit Return Types', 'delete Return Types'])
                                            <td>
                                                @can('edit Return Types')
                                                    <a href="{{ route('return-types.edit', $type->id) }}" class="btn btn-success btn-icon-square-sm"><i class="las la-edit"></i></a>
                                                @endcan
                                                @can('delete Return Types')
                                                    <form action="{{ route('return-types.destroy', $type->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('{{ __('crm::crm.confirm_delete_return_type') }}');">
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
                        @if($returnTypes->hasPages())
                            <div class="mt-3 d-flex justify-content-center">{{ $returnTypes->links() }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection