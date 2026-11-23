@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.purchases-invoices')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('invoices::invoices.requisitions'),
        'breadcrumb_items' => [
            ['label' => __('invoices::invoices.dashboard'), 'url' => route('admin.dashboard')],
            ['label' => __('invoices::invoices.requisitions')],
        ],
    ])

    {{-- فلاتر البحث --}}
    <div class="row mb-3">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body py-3">
                    <form method="GET" action="{{ route('invoices.requests.index') }}" class="row g-2 align-items-end">

                        {{-- نوع الفاتورة --}}
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">{{ __('invoices::invoices.invoice_type') }}</label>
                            <select name="type" class="form-select form-select-sm">
                                <option value="">{{ __('invoices::invoices.all') }}</option>
                                <option value="25" {{ request('type') == 25 ? 'selected' : '' }}>{{ __('invoices::invoices.requisition') }}</option>
                                <option value="17" {{ request('type') == 17 ? 'selected' : '' }}>{{ __('invoices::invoices.quotation_from_supplier') }}</option>
                                <option value="15" {{ request('type') == 15 ? 'selected' : '' }}>{{ __('invoices::invoices.purchase_order') }}</option>
                                <option value="11" {{ request('type') == 11 ? 'selected' : '' }}>{{ __('invoices::invoices.purchase_invoice') }}</option>
                                <option value="19" {{ request('type') == 19 ? 'selected' : '' }}>{{ __('invoices::invoices.dispatch_order') }}</option>
                            </select>
                        </div>

                        {{-- حالة الـ Workflow --}}
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">{{ __('invoices::invoices.invoice_status') }}</label>
                            <select name="workflow_state" class="form-select form-select-sm">
                                <option value="">{{ __('invoices::invoices.all') }}</option>
                                <option value="1" {{ request('workflow_state') == 1 ? 'selected' : '' }}>{{ __('invoices::invoices.requisition') }}</option>
                                <option value="2" {{ request('workflow_state') == 2 ? 'selected' : '' }}>{{ __('invoices::invoices.quotation_from_supplier') }}</option>
                                <option value="3" {{ request('workflow_state') == 3 ? 'selected' : '' }}>{{ __('invoices::invoices.purchase_order') }}</option>
                                <option value="4" {{ request('workflow_state') == 4 ? 'selected' : '' }}>{{ __('invoices::invoices.purchase_invoice') }}</option>
                                <option value="5" {{ request('workflow_state') == 5 ? 'selected' : '' }}>{{ __('invoices::invoices.dispatch_order') }}</option>
                            </select>
                        </div>

                        {{-- من تاريخ --}}
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">{{ __('invoices::invoices.from_date') }}</label>
                            <input type="date" name="from_date" class="form-control form-control-sm"
                                value="{{ request('from_date') }}">
                        </div>

                        {{-- إلى تاريخ --}}
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">{{ __('invoices::invoices.to_date') }}</label>
                            <input type="date" name="to_date" class="form-control form-control-sm"
                                value="{{ request('to_date') }}">
                        </div>

                        {{-- رقم المستند --}}
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">{{ __('invoices::invoices.document_number') }}</label>
                            <input type="number" name="pro_id" class="form-control form-control-sm"
                                placeholder="#" value="{{ request('pro_id') }}">
                        </div>

                        {{-- أزرار --}}
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-main btn-sm flex-fill">
                                <i class="las la-search"></i> {{ __('invoices::invoices.filter') }}
                            </button>
                            <a href="{{ route('invoices.requests.index') }}" class="btn btn-outline-secondary btn-sm flex-fill">
                                <i class="las la-times"></i> {{ __('invoices::invoices.all') }}
                            </a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive" style="overflow-x: auto;">
                        {{-- أزرار التصدير --}}
                        <x-table-export-actions table-id="purchase-requests-table" filename="purchase-requests"
                            excel-label="{{ __('invoices::invoices.export_excel') }}" pdf-label="{{ __('invoices::invoices.export_pdf') }}"
                            print-label="{{ __('invoices::invoices.print') }}" />


                        <table id="purchase-requests-table" class="table table-striped mb-0 text-center align-middle"
                            style="min-width: 1200px;">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('invoices::invoices.document_number') }}</th>
                                    <th>{{ __('invoices::invoices.invoice_type') }}</th>
                                    <th>{{ __('invoices::invoices.date') }}</th>
                                    <th>{{ __('invoices::invoices.amount') }}</th>
                                    <th>{{ __('invoices::invoices.invoice_status') }}</th>
                                    <th>{{ __('invoices::invoices.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $typeLabels = [
                                        25 => __('invoices::invoices.requisition'),
                                        17 => __('invoices::invoices.quotation_from_supplier'),
                                        15 => __('invoices::invoices.purchase_order'),
                                        11 => __('invoices::invoices.purchase_invoice'),
                                        19 => __('invoices::invoices.dispatch_order'),
                                    ];
                                    $typeBadges = [
                                        25 => 'bg-secondary',
                                        17 => 'bg-info',
                                        15 => 'bg-primary',
                                        11 => 'bg-success',
                                        19 => 'bg-warning text-dark',
                                    ];
                                    $workflowLabels = [
                                        1 => ['label' => __('invoices::invoices.requisition'),           'class' => 'bg-secondary'],
                                        2 => ['label' => __('invoices::invoices.quotation_from_supplier'),'class' => 'bg-info'],
                                        3 => ['label' => __('invoices::invoices.purchase_order'),         'class' => 'bg-primary'],
                                        4 => ['label' => __('invoices::invoices.purchase_invoice'),       'class' => 'bg-success'],
                                        5 => ['label' => __('invoices::invoices.dispatch_order'),         'class' => 'bg-warning text-dark'],
                                    ];
                                @endphp
                                @forelse ($requests as $req)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $req->pro_id }}</td>
                                        <td>
                                            <span class="badge {{ $typeBadges[$req->pro_type] ?? 'bg-secondary' }}">
                                                {{ $typeLabels[$req->pro_type] ?? $req->pro_type }}
                                            </span>
                                        </td>
                                        <td>{{ $req->pro_date }}</td>
                                        <td>{{ number_format($req->pro_value, 2) }}</td>
                                        <td>
                                            @php
                                                $ws = (int) $req->workflow_state;
                                                $wInfo = $workflowLabels[$ws] ?? null;
                                            @endphp
                                            @if ($wInfo)
                                                <span class="badge {{ $wInfo['class'] }}">{{ $wInfo['label'] }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('invoices::invoices.unknown') }}</span>
                                            @endif
                                        </td>


                                        {{-- @canany(['تتبع طلب الاحتياج', 'تأكيد طلب الاحتياج']) --}}
                                        <td>
                                            {{-- @can('تتبع طلب الاحتياج') --}}
                                            <a href="{{ route('invoices.track', ['id' => $req->id]) }}"
                                                class="btn btn-secondary">
                                                <i class="las la-route"></i> {{ __('invoices::invoices.track_order_stages') }}
                                            </a>
                                            {{-- @endcan --}}

                                            @php
                                                $manufacturingItems = $req->operationItems->map(function ($item) {
                                                    return [
                                                        'id'   => $item->item_id,
                                                        'name' => $item->item->name ?? 'Unknown',
                                                        'qty'  => $item->qty_in ?? $item->qty,
                                                    ];
                                                })->values()->toJson();
                                            @endphp
                                            <button type="button" class="btn btn-info"
                                                onclick='Livewire.dispatch("openManufacturingModal", { items: {{ $manufacturingItems }} })'>
                                                <i class="las la-industry"></i> {{ __('invoices::invoices.manufacturing_details') }}
                                            </button>


                                            {{-- مثال على الموافقة (تقدر تفعلها لما تحتاج) --}}
                                            {{--
                                                @can('تأكيد طلب الاحتياج')
                                                    <form action="{{ route('invoices.confirm', ['id' => $req->id]) }}"
                                                          method="POST" style="display:inline-block;">
                                                        @csrf
                                                        <input type="hidden" name="next_stage" value="1">
                                                        <button class="btn btn-success btn-icon-square-sm">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                                --}}
                                        </td>
                                        {{-- @endcanany --}}
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <div class="alert alert-info py-3 mb-0"
                                                style="font-size: 1.2rem; font-weight: 500;">
                                                <i class="las la-info-circle me-2"></i>
                                                {{ __('invoices::invoices.no_requests_at_moment') }}
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>


                    <div class="mt-3">
                        {{ $requests->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- <livewire:manufacturing::manufacturing-cost-modal /> --}}
@endsection
