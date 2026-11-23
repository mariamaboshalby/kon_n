@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.reports')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('reports::reports.restaurant_analytics_report'),
        'breadcrumb_items' => [
            ['label' => __('reports::reports.home'), 'url' => route('admin.dashboard')],
            ['label' => __('reports::reports.sales_reports')],
            ['label' => __('reports::reports.restaurant_analytics_report')],
        ],
    ])

    <div class="container-fluid">

        {{-- فلاتر --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">{{ __('reports::reports.from_date') }}</label>
                        <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">{{ __('reports::reports.to_date') }}</label>
                        <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">{{ __('reports::reports.branch') }}</label>
                        <select name="branch_id" class="form-select">
                            <option value="">{{ __('reports::reports.all') }}</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fas fa-search me-1"></i> {{ __('reports::reports.search') }}
                        </button>
                        <a href="{{ route('reports.pos.restaurant-analytics') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- بطاقات الإجماليات --}}
        <div class="row g-3 mb-4">
            <div class="col-md-2">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1"><i class="fas fa-receipt text-primary me-1"></i>{{ __('reports::reports.total_orders') }}</div>
                        <div class="fs-3 fw-bold text-primary">{{ number_format($totals->total_orders ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1"><i class="fas fa-coins text-success me-1"></i>{{ __('reports::reports.net_total') }}</div>
                        <div class="fs-3 fw-bold text-success">{{ number_format($totals->net_sales ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1"><i class="fas fa-chair text-info me-1"></i>{{ __('reports::reports.dine_in') }}</div>
                        <div class="fs-3 fw-bold text-info">{{ number_format($totals->dine_in_count ?? 0) }}</div>
                        <div class="text-muted small">{{ number_format($totals->dine_in_sales ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1"><i class="fas fa-motorcycle text-warning me-1"></i>{{ __('reports::reports.delivery') }}</div>
                        <div class="fs-3 fw-bold text-warning">{{ number_format($totals->delivery_count ?? 0) }}</div>
                        <div class="text-muted small">{{ number_format($totals->delivery_sales ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1"><i class="fas fa-shopping-bag text-secondary me-1"></i>{{ __('reports::reports.takeaway') }}</div>
                        <div class="fs-3 fw-bold text-secondary">{{ number_format($totals->takeaway_count ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1"><i class="fas fa-calculator text-danger me-1"></i>{{ __('reports::reports.average_invoice') }}</div>
                        <div class="fs-3 fw-bold text-danger">{{ number_format($totals->avg_order ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            {{-- رسم بياني ساعي --}}
            <div class="col-md-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-clock text-warning me-2"></i>{{ __('reports::reports.hourly_sales') }}
                    </div>
                    <div class="card-body">
                        <canvas id="hourlyChart" height="120"></canvas>
                    </div>
                </div>
            </div>

            {{-- أفضل الأصناف --}}
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
                                        <th class="text-end">{{ __('reports::reports.total_quantity') }}</th>
                                        <th class="text-end">{{ __('reports::reports.total') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topItems as $i => $item)
                                        <tr>
                                            <td class="text-muted">{{ $i + 1 }}</td>
                                            <td>{{ $item->name }}</td>
                                            <td class="text-end">{{ number_format($item->qty, 1) }}</td>
                                            <td class="text-end fw-bold">{{ number_format($item->total, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-3">{{ __('reports::reports.no_data_available') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- جدول يومي --}}
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold">
                <i class="fas fa-table text-secondary me-2"></i>{{ __('reports::reports.daily_details') }}
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('reports::reports.date') }}</th>
                                <th class="text-end">{{ __('reports::reports.total_orders') }}</th>
                                <th class="text-end">{{ __('reports::reports.net_total') }}</th>
                                <th class="text-end">{{ __('reports::reports.average_invoice') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($daily as $row)
                                <tr>
                                    <td>{{ $row->day }}</td>
                                    <td class="text-end"><span class="badge bg-info">{{ $row->orders_count }}</span></td>
                                    <td class="text-end fw-bold text-success">{{ number_format($row->net_sales, 2) }}</td>
                                    <td class="text-end">{{ number_format($row->avg_order, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">{{ __('reports::reports.no_data_available') }}</td></tr>
                            @endforelse
                        </tbody>
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
    @php
        $hourlyMap    = $hourly->keyBy('hour');
        $hours24      = collect(range(0, 23));
        $hourlyLabels = $hours24->map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00');
        $hourlyOrders = $hours24->map(fn($h) => (int)   ($hourlyMap->get($h)?->orders_count ?? 0));
        $hourlySales  = $hours24->map(fn($h) => (float) ($hourlyMap->get($h)?->net_sales    ?? 0));
    @endphp
    const hours  = @json($hourlyLabels);
    const orders = @json($hourlyOrders);
    const sales  = @json($hourlySales);

    new Chart(document.getElementById('hourlyChart'), {
        type: 'bar',
        data: {
            labels: hours,
            datasets: [
                { label: '{{ __('reports::reports.total_orders') }}', data: orders, backgroundColor: '#ffc107', yAxisID: 'y1' },
                { label: '{{ __('reports::reports.net_total') }}',    data: sales,  backgroundColor: '#198754', type: 'line', yAxisID: 'y2', tension: 0.4 }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: {
                y1: { type: 'linear', position: 'left',  beginAtZero: true, title: { display: true, text: '{{ __('reports::reports.orders') }}' } },
                y2: { type: 'linear', position: 'right', beginAtZero: true, title: { display: true, text: '{{ __('reports::reports.net_total') }}' }, grid: { drawOnChartArea: false } }
            }
        }
    });
})();
</script>
@endpush
