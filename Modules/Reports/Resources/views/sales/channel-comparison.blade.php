@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.reports')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('reports::reports.channel_comparison_report'),
        'breadcrumb_items' => [
            ['label' => __('reports::reports.home'), 'url' => route('admin.dashboard')],
            ['label' => __('reports::reports.sales_reports')],
            ['label' => __('reports::reports.channel_comparison_report')],
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
                        <a href="{{ route('reports.pos.channel-comparison') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        @php
            $grand = $channelTotals->grand_total ?? 0;
            $salesPct      = $grand > 0 ? ($channelTotals->sales_total      / $grand * 100) : 0;
            $cashierPct    = $grand > 0 ? ($channelTotals->cashier_total    / $grand * 100) : 0;
            $restaurantPct = $grand > 0 ? ($channelTotals->restaurant_total / $grand * 100) : 0;
        @endphp

        {{-- مقارنة القنوات --}}
        <div class="row g-3 mb-4">
            {{-- مبيعات عادية --}}
            <div class="col-md-4">
                <div class="card border-start border-4 border-primary shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0"><i class="fas fa-file-invoice text-primary me-2"></i>{{ __('reports::reports.sales_invoice') }}</h6>
                            <span class="badge bg-primary fs-6">{{ number_format($salesPct, 1) }}%</span>
                        </div>
                        <div class="fs-3 fw-bold text-primary mb-1">{{ number_format($channelTotals->sales_total ?? 0, 2) }}</div>
                        <div class="progress mb-2" style="height:6px"><div class="progress-bar bg-primary" style="width:{{ $salesPct }}%"></div></div>
                        <div class="row text-center text-muted small">
                            <div class="col">{{ __('reports::reports.invoices_count') }}<br><strong>{{ number_format($channelTotals->sales_count ?? 0) }}</strong></div>
                            <div class="col">{{ __('reports::reports.average_invoice') }}<br><strong>{{ number_format($channelTotals->sales_avg ?? 0, 2) }}</strong></div>
                            <div class="col">{{ __('reports::reports.total_discount') }}<br><strong>{{ number_format($channelTotals->sales_discount ?? 0, 2) }}</strong></div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- كاشير --}}
            <div class="col-md-4">
                <div class="card border-start border-4 border-success shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0"><i class="fas fa-cash-register text-success me-2"></i>{{ __('pos.cashier_pos') }}</h6>
                            <span class="badge bg-success fs-6">{{ number_format($cashierPct, 1) }}%</span>
                        </div>
                        <div class="fs-3 fw-bold text-success mb-1">{{ number_format($channelTotals->cashier_total ?? 0, 2) }}</div>
                        <div class="progress mb-2" style="height:6px"><div class="progress-bar bg-success" style="width:{{ $cashierPct }}%"></div></div>
                        <div class="row text-center text-muted small">
                            <div class="col">{{ __('reports::reports.invoices_count') }}<br><strong>{{ number_format($channelTotals->cashier_count ?? 0) }}</strong></div>
                            <div class="col">{{ __('reports::reports.average_invoice') }}<br><strong>{{ number_format($channelTotals->cashier_avg ?? 0, 2) }}</strong></div>
                            <div class="col">{{ __('reports::reports.total_discount') }}<br><strong>{{ number_format($channelTotals->cashier_discount ?? 0, 2) }}</strong></div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- مطعم --}}
            <div class="col-md-4">
                <div class="card border-start border-4 border-warning shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0"><i class="fas fa-utensils text-warning me-2"></i>{{ __('pos.restaurant_pos') }}</h6>
                            <span class="badge bg-warning text-dark fs-6">{{ number_format($restaurantPct, 1) }}%</span>
                        </div>
                        <div class="fs-3 fw-bold text-warning mb-1">{{ number_format($channelTotals->restaurant_total ?? 0, 2) }}</div>
                        <div class="progress mb-2" style="height:6px"><div class="progress-bar bg-warning" style="width:{{ $restaurantPct }}%"></div></div>
                        <div class="row text-center text-muted small">
                            <div class="col">{{ __('reports::reports.invoices_count') }}<br><strong>{{ number_format($channelTotals->restaurant_count ?? 0) }}</strong></div>
                            <div class="col">{{ __('reports::reports.average_invoice') }}<br><strong>{{ number_format($channelTotals->restaurant_avg ?? 0, 2) }}</strong></div>
                            <div class="col">{{ __('reports::reports.total_discount') }}<br><strong>{{ number_format($channelTotals->restaurant_discount ?? 0, 2) }}</strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            {{-- رسم بياني الاتجاه --}}
            <div class="col-md-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-chart-line text-primary me-2"></i>{{ __('reports::reports.daily_trend_by_channel') }}
                    </div>
                    <div class="card-body">
                        <canvas id="trendChart" height="130"></canvas>
                    </div>
                </div>
            </div>

            {{-- رسم دائري --}}
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-chart-pie text-warning me-2"></i>{{ __('reports::reports.channel_distribution') }}
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <canvas id="pieChart" height="220"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- أفضل أصناف لكل قناة --}}
        <div class="row g-4">
            @foreach(['sales' => ['label' => __('reports::reports.sales_invoice'), 'color' => 'primary'], 'cashier' => ['label' => __('pos.cashier_pos'), 'color' => 'success'], 'restaurant' => ['label' => __('pos.restaurant_pos'), 'color' => 'warning']] as $key => $meta)
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white fw-bold">
                            <i class="fas fa-trophy text-{{ $meta['color'] }} me-2"></i>{{ __('reports::reports.top_items') }} - {{ $meta['label'] }}
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
                                        @forelse($topItemsByChannel[$key] as $i => $item)
                                            <tr>
                                                <td class="text-muted">{{ $i + 1 }}</td>
                                                <td>{{ $item->name }}</td>
                                                <td class="text-end fw-bold">{{ number_format($item->total, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="text-center text-muted py-2">{{ __('reports::reports.no_data_available') }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function () {
    const days       = @json($dailyTrend->pluck('day'));
    const salesData  = @json($dailyTrend->pluck('sales_total'));
    const cashierData= @json($dailyTrend->pluck('cashier_total'));
    const restData   = @json($dailyTrend->pluck('restaurant_total'));

    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: days,
            datasets: [
                { label: '{{ __('reports::reports.sales_invoice') }}', data: salesData,   borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,.1)', tension: 0.4, fill: true },
                { label: '{{ __('pos.cashier_pos') }}',                data: cashierData, borderColor: '#198754', backgroundColor: 'rgba(25,135,84,.1)',  tension: 0.4, fill: true },
                { label: '{{ __('pos.restaurant_pos') }}',             data: restData,    borderColor: '#ffc107', backgroundColor: 'rgba(255,193,7,.1)',   tension: 0.4, fill: true },
            ]
        },
        options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
    });

    new Chart(document.getElementById('pieChart'), {
        type: 'doughnut',
        data: {
            labels: ['{{ __('reports::reports.sales_invoice') }}', '{{ __('pos.cashier_pos') }}', '{{ __('pos.restaurant_pos') }}'],
            datasets: [{ data: [{{ $channelTotals->sales_total ?? 0 }}, {{ $channelTotals->cashier_total ?? 0 }}, {{ $channelTotals->restaurant_total ?? 0 }}], backgroundColor: ['#0d6efd','#198754','#ffc107'] }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
})();
</script>
@endpush
