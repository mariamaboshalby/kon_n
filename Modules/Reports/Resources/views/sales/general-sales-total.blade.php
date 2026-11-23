@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.reports')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('reports::reports.sales_totals_report'),
        'breadcrumb_items' => [
            ['label' => __('reports::reports.home'), 'url' => route('admin.dashboard')],
            ['label' => __('reports::reports.sales_totals_report')],
        ],
    ])

    <div class="container-fluid">

        {{-- فلتر التاريخ --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label fw-bold">{{ __('reports::reports.from_date') }}</label>
                        <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">{{ __('reports::reports.to_date') }}</label>
                        <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">{{ __('reports::reports.branch') }}</label>
                        <select name="branch_id" class="form-select">
                            <option value="">{{ __('reports::reports.all') }}</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">{{ __('reports::reports.user') }}</label>
                        <select name="user_id" class="form-select">
                            <option value="">{{ __('reports::reports.all') }}</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fas fa-search me-1"></i> {{ __('reports::reports.search') }}
                        </button>
                        <a href="{{ route('reports.sales.total') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- بطاقات الإجماليات --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-muted small mb-1">{{ __('reports::reports.net_total') }}</div>
                        <div class="fs-3 fw-bold text-primary">{{ number_format($totals->net_total ?? 0, 2) }}</div>
                        <div class="text-muted small">{{ __('reports::reports.invoices_count') }}: {{ number_format($totals->invoices_count ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-muted small mb-1"><i class="fas fa-cash-register text-success me-1"></i>{{ __('pos.cashier_pos') }}</div>
                        <div class="fs-3 fw-bold text-success">{{ number_format($totals->cashier_total ?? 0, 2) }}</div>
                        <div class="text-muted small">{{ number_format($totals->cashier_count ?? 0) }} {{ __('reports::reports.invoice') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-muted small mb-1"><i class="fas fa-utensils text-warning me-1"></i>{{ __('pos.restaurant_pos') }}</div>
                        <div class="fs-3 fw-bold text-warning">{{ number_format($totals->restaurant_total ?? 0, 2) }}</div>
                        <div class="text-muted small">{{ number_format($totals->restaurant_count ?? 0) }} {{ __('reports::reports.invoice') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-muted small mb-1">{{ __('reports::reports.avg_invoice') }}</div>
                        <div class="fs-3 fw-bold text-info">{{ number_format($avgInvoice, 2) }}</div>
                        <div class="text-muted small">{{ __('reports::reports.total_discount') }}: {{ number_format($totals->total_discount ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            {{-- رسم بياني يومي --}}
            <div class="col-md-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-chart-bar text-primary me-2"></i>{{ __('reports::reports.daily_sales') }}
                    </div>
                    <div class="card-body">
                        <canvas id="dailyChart" height="120"></canvas>
                    </div>
                </div>
            </div>

            {{-- أفضل 10 أصناف --}}
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-trophy text-warning me-2"></i>{{ __('reports::reports.top_items') }}
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('reports::reports.item') }}</th>
                                        <th class="text-end">{{ __('reports::reports.total') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topItems as $i => $item)
                                        <tr>
                                            <td class="text-muted">{{ $i + 1 }}</td>
                                            <td>{{ $item->name }}</td>
                                            <td class="text-end fw-bold">{{ number_format($item->total, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-muted py-3">{{ __('reports::reports.no_data') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- جدول تفصيلي يومي --}}
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-white fw-bold">
                <i class="fas fa-table text-secondary me-2"></i>{{ __('reports::reports.daily_details') }}
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('reports::reports.date') }}</th>
                                <th class="text-end">{{ __('pos.cashier_pos') }}</th>
                                <th class="text-end">{{ __('pos.restaurant_pos') }}</th>
                                <th class="text-end">{{ __('reports::reports.net_total') }}</th>
                                <th class="text-end">{{ __('reports::reports.invoices_count') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($daily as $row)
                                <tr>
                                    <td>{{ $row->day }}</td>
                                    <td class="text-end text-success">{{ number_format($row->cashier_total, 2) }}</td>
                                    <td class="text-end text-warning">{{ number_format($row->restaurant_total, 2) }}</td>
                                    <td class="text-end fw-bold">{{ number_format($row->net_total, 2) }}</td>
                                    <td class="text-end">{{ $row->invoices_count }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">{{ __('reports::reports.no_data') }}</td></tr>
                            @endforelse
                        </tbody>
                        @if($daily->count())
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td>{{ __('reports::reports.total') }}</td>
                                <td class="text-end text-success">{{ number_format($totals->cashier_total ?? 0, 2) }}</td>
                                <td class="text-end text-warning">{{ number_format($totals->restaurant_total ?? 0, 2) }}</td>
                                <td class="text-end">{{ number_format($totals->net_total ?? 0, 2) }}</td>
                                <td class="text-end">{{ number_format($totals->invoices_count ?? 0) }}</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function () {
    const labels  = @json($daily->pluck('day'));
    const cashier = @json($daily->pluck('cashier_total'));
    const rest    = @json($daily->pluck('restaurant_total'));

    new Chart(document.getElementById('dailyChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: '{{ __('pos.cashier_pos') }}',    data: cashier, backgroundColor: '#198754' },
                { label: '{{ __('pos.restaurant_pos') }}', data: rest,    backgroundColor: '#ffc107' },
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } }
        }
    });
})();
</script>
@endpush
