@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.reports')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('reports::reports.sales_invoices_report'),
        'breadcrumb_items' => [['label' => __('reports::reports.home'), 'url' => route('admin.dashboard')], ['label' => __('reports::reports.sales_invoices')]],
    ])
    <div class="row">
        <div class="col-lg-12">
            <br><br>
            {{-- Filters Card --}}
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ request()->url() }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label">{{ __('reports::reports.from_date') }}</label>
                                <input type="date" name="from_date" class="form-control"
                                    value="{{ request('from_date') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('reports::reports.to_date') }}</label>
                                <input type="date" name="to_date" class="form-control"
                                    value="{{ request('to_date') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('reports::reports.type') }}</label>
                                <select name="pro_type" class="form-select">
                                    <option value="">{{ __('reports::reports.all') }}</option>
                                    <option value="10" @selected(request('pro_type') == '10')>
                                        {{ __('reports::reports.sales') }}
                                    </option>
                                    <option value="13" @selected(request('pro_type') == '13')>
                                        {{ __('reports::reports.sales_return') }}
                                    </option>
                                    <option value="102" @selected(request('pro_type') == '102')>
                                        {{ __('reports::reports.pos_cashier') }}
                                    </option>
                                    <option value="103" @selected(request('pro_type') == '103')>
                                        {{ __('reports::reports.pos_restaurant') }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('reports::reports.customer') }}</label>
                                <select name="customer_id" class="form-select">
                                    <option value="">{{ __('reports::reports.all_customers') }}</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}"
                                            @selected(request('customer_id') == $customer->id)>
                                            {{ $customer->aname }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('reports::reports.employee') }}</label>
                                <select name="employee_id" class="form-select">
                                    <option value="">{{ __('reports::reports.all') }}</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}"
                                            @selected(request('employee_id') == $employee->id)>
                                            {{ $employee->aname }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('reports::reports.user') }}</label>
                                <select name="user_id" class="form-select">
                                    <option value="">{{ __('reports::reports.all') }}</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}"
                                            @selected(request('user_id') == $user->id)>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('reports::reports.invoice_number') }}</label>
                                <input type="text" name="invoice_number" class="form-control"
                                    value="{{ request('invoice_number') }}"
                                    placeholder="{{ __('reports::reports.invoice_number') }}">
                            </div>
                            <div class="col-md-2 d-flex gap-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-1"></i>{{ __('reports::reports.filter') }}
                                </button>
                                <a href="{{ request()->url() }}" class="btn btn-secondary w-100">
                                    <i class="fas fa-times me-1"></i>{{ __('reports::reports.reset') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive" style="overflow-x: auto;">
                        <x-table-export-actions table-id="sales-invoice-report-table" filename="sales-invoice-report-table"
                            excel-label="{{ __('reports::reports.export_excel') }}" pdf-label="{{ __('reports::reports.export_pdf') }}"
                            print-label="{{ __('reports::reports.print') }}" />

                        <table id="sales-invoice-report-table" class="table table-bordered table-striped text-center"
                            style="min-width: 1200px;">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('reports::reports.invoice_number') }}</th>
                                    <th>{{ __('reports::reports.type') }}</th>
                                    <th>{{ __('reports::reports.balance_value') }}</th>
                                    <th>{{ __('reports::reports.profit') }}</th>
                                    <th>{{ __('reports::reports.invoice_date') }}</th>
                                    <th>{{ __('reports::reports.account_entry') }}</th>
                                    <th>{{ __('reports::reports.account_entry') }}</th>
                                    <th>{{ __('reports::reports.employee') }}</th>
                                    <th>{{ __('reports::reports.user') }}</th>
                                    <th>{{ __('reports::reports.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($invoices as $invoice)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $invoice->pro_id }}</td>
                                        <td>
                                            @if ($invoice->pro_type == 10)
                                                <span class="badge bg-primary">{{ __('reports::reports.sales') }}</span>
                                            @elseif($invoice->pro_type == 13)
                                                <span class="badge bg-warning">{{ __('reports::reports.sales_return') }}</span>
                                            @elseif($invoice->pro_type == 102)
                                                <span class="badge bg-info">{{ __('reports::reports.pos_cashier') }}</span>
                                            @elseif($invoice->pro_type == 103)
                                                <span class="badge bg-success">{{ __('reports::reports.pos_restaurant') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ number_format($invoice->pro_value, 2) }}</td>
                                        <td
                                            class="fw-bold {{ $invoice->profit > 0 ? 'bg-success text-white' : ($invoice->profit < 0 ? 'bg-danger text-white' : 'bg-secondary text-white') }}">
                                            {{ number_format($invoice->profit, 2) }}
                                        </td>
                                        <td>{{ $invoice->pro_date }}</td>
                                        <td>{{ $invoice->acc1Head->aname ?? '-' }}</td>
                                        <td>{{ $invoice->acc2Head->aname ?? '-' }}</td>
                                        <td>{{ $invoice->employee->aname ?? '-' }}</td>
                                        <td>{{ $invoice->createdByUser->name ?? '-' }}</td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                <a class="btn btn-primary btn-icon-square-sm"
                                                    href="{{ route('invoices.edit', $invoice->id) }}"
                                                    title="{{ __('reports::reports.view_edit') }}">
                                                    <i class="fas fa-eye"></i>
                                                </a>

                                                <form action="{{ route('invoices.destroy', $invoice->id) }}" method="POST"
                                                    style="display:inline-block;"
                                                    onsubmit="return confirm('{{ __('reports::reports.are_you_sure_want_to_delete_this_record') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-icon-square-sm"
                                                        title="{{ __('reports::reports.delete') }}">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center">
                                            <div class="alert alert-info py-3 mb-0"
                                                style="font-size: 1.2rem; font-weight: 500;">
                                                <i class="fas fa-info-circle me-2"></i>
                                                {{ __('reports::reports.no_data_available_yet') }}
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-center mt-3">
                            {{ $invoices->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
