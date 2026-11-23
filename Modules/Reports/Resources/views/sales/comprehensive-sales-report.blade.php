@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.reports')
@endsection

@section('styles')
<style>
    .kpi-card { border-radius: 12px; transition: transform .15s; }
    .kpi-card:hover { transform: translateY(-3px); }
    .kpi-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
    .growth-badge { font-size: .75rem; padding: 3px 8px; border-radius: 20px; }
    .chart-container { position: relative; height: 280px; }
    .section-title { font-size: 1rem; font-weight: 700; color: #344767; border-right: 4px solid #5e72e4; padding-right: 10px; margin-bottom: 1rem; }
    .top-item-bar { height: 8px; border-radius: 4px; background: linear-gradient(90deg, #5e72e4, #825ee4); }
    .table-rank { width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: .8rem; }
</style>
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('reports::reports.comprehensive_sales_report'),
        'breadcrumb_items' => [
            ['label' => __('reports::reports.home'), 'url' => route('admin.dashboard')],
            ['label' => __('reports::reports.sales_reports'), 'url' => '#'],
            ['label' => __('reports::reports.comprehensive_sales_report')],
        ],
    ])

    <div class="container-fluid">

        {{-- فلاتر --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body py-3">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small fw-bold mb-1">{{ __('reports::reports.from_date') }}</label>
                        <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold mb-1">{{ __('reports::reports.to_date') }}</label>
                        <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold mb-1">{{ __('reports::reports.sale_type') }}</label>
                        <select name="sale_type" class="form-select form-select-sm">
                            <option value="">{{ __('reports::reports.all_channels') }}</option>
                            <option value="10"  {{ $saleType == '10'  ? 'selected' : '' }}>{{ __('reports::reports.sales_invoice_channel') }}</option>
                            <option value="102" {{ $saleType == '102' ? 'selected' : '' }}>{{ __('reports::reports.cashier_channel') }}</option>
                            <option value="103" {{ $saleType == '103' ? 'selected' : '' }}>{{ __('reports::reports.restaurant_channel') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold mb-1">{{ __('reports::reports.branch') }}</label>
                        <select name="branch_id" class="form-select form-select-sm">
                            <option value="">{{ __('reports::reports.all') }}</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill">
                            <i class="las la-filter me-1"></i>{{ __('reports::reports.filter') }}
                        </button>
                        <a href="{{ route('reports.sales.comprehensive') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="las la-redo-alt"></i>
                        </a>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="window.print()">
                            <i class="las la-print"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- KPI Cards --}}
        @php
            $netTotal      = (float) ($totals->net_total      ?? 0);
            $grossTotal    = (float) ($totals->gross_total    ?? 0);
            $totalDiscount = (float) ($totals->total_discount ?? 0);
            $totalProfit   = (float) ($totals->total_profit   ?? 0);
            $invoicesCount = (int)   ($totals->invoices_count ?? 0);
            $avgInvoice    = (float) ($totals->avg_invoice    ?? 0);
            $returnsVal    = (float) ($returnTotals->returns_value ?? 0);
            $returnsCount  = (int)   ($returnTotals->returns_count ?? 0);
        @endphp

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card kpi-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="kpi-icon bg-primary bg-opacity-10 text-primary"><i class="las la-chart-line"></i></div>
                            @if($growthPct !== null)
                                <span class="growth-badge {{ $growthPct >= 0 ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }}">
                                    <i class="las la-{{ $growthPct >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                                    {{ number_format(abs($growthPct), 1) }}%
                                </span>
                            @endif
                        </div>
                        <div class="fs-4 fw-bold text-primary">{{ number_format($netTotal, 2) }}</div>
                        <div class="text-muted small">{{ __('reports::reports.net_sales_total') }}</div>
                        <div class="text-muted" style="font-size:.7rem">{{ __('reports::reports.previous_period') }}: {{ number_format($prevTotals->net_total ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card kpi-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="kpi-icon bg-success bg-opacity-10 text-success"><i class="las la-file-invoice"></i></div>
                            <span class="text-muted small">{{ $days }} {{ __('reports::reports.days') }}</span>
                        </div>
                        <div class="fs-4 fw-bold text-success">{{ number_format($invoicesCount) }}</div>
                        <div class="text-muted small">{{ __('reports::reports.total_invoices_count') }}</div>
                        <div class="text-muted" style="font-size:.7rem">{{ __('reports::reports.avg_invoice_value') }}: {{ number_format($avgInvoice, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card kpi-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="kpi-icon bg-warning bg-opacity-10 text-warning"><i class="las la-percentage"></i></div>
                            <span class="badge {{ $profitMarginPct >= 20 ? 'bg-success' : ($profitMarginPct >= 10 ? 'bg-warning' : 'bg-danger') }}">
                                {{ number_format($profitMarginPct, 1) }}%
                            </span>
                        </div>
                        <div class="fs-4 fw-bold text-warning">{{ number_format($totalProfit, 2) }}</div>
                        <div class="text-muted small">{{ __('reports::reports.profit_margin') }}</div>
                        <div class="text-muted" style="font-size:.7rem">{{ __('reports::reports.discount_rate') }}: {{ number_format($discountPct, 1) }}% ({{ number_format($totalDiscount, 2) }})</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card kpi-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="kpi-icon bg-danger bg-opacity-10 text-danger"><i class="las la-undo"></i></div>
                            @if($netTotal > 0)
                                <span class="growth-badge bg-danger bg-opacity-10 text-danger">{{ number_format(($returnsVal / $netTotal) * 100, 1) }}%</span>
                            @endif
                        </div>
                        <div class="fs-4 fw-bold text-danger">{{ number_format($returnsVal, 2) }}</div>
                        <div class="text-muted small">{{ __('reports::reports.returns_value') }}</div>
                        <div class="text-muted" style="font-size:.7rem">{{ __('reports::reports.returns_count') }}: {{ number_format($returnsCount) }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- اتجاه المبيعات + توزيع القنوات --}}
        <div class="row g-4 mb-4">
            <div class="col-md-8">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="section-title">{{ __('reports::reports.sales_trend') }}</div>
                        <div class="chart-container"><canvas id="trendChart"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="section-title">{{ __('reports::reports.sales_channel_distribution') }}</div>
                        <div class="chart-container"><canvas id="channelChart"></canvas></div>
                        <div class="mt-3">
                            @php
                                $salesT = (float)($totals->sales_total      ?? 0);
                                $cashT  = (float)($totals->cashier_total    ?? 0);
                                $restT  = (float)($totals->restaurant_total ?? 0);
                                $salesC = (int)  ($totals->sales_count      ?? 0);
                                $cashC  = (int)  ($totals->cashier_count    ?? 0);
                                $restC  = (int)  ($totals->restaurant_count ?? 0);
                            @endphp
                            <div class="d-flex justify-content-between small mb-1">
                                <span><span class="badge bg-primary me-1">&nbsp;</span>{{ __('reports::reports.sales_invoice_channel') }}</span>
                                <span class="fw-bold">{{ number_format($salesT, 2) }} <span class="text-muted">({{ $salesC }})</span></span>
                            </div>
                            <div class="d-flex justify-content-between small mb-1">
                                <span><span class="badge bg-success me-1">&nbsp;</span>{{ __('reports::reports.cashier_channel') }}</span>
                                <span class="fw-bold">{{ number_format($cashT, 2) }} <span class="text-muted">({{ $cashC }})</span></span>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span><span class="badge bg-warning me-1">&nbsp;</span>{{ __('reports::reports.restaurant_channel') }}</span>
                                <span class="fw-bold">{{ number_format($restT, 2) }} <span class="text-muted">({{ $restC }})</span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- أفضل 10 أصناف + أفضل 10 عملاء --}}
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="section-title">{{ __('reports::reports.top_10_items') }}</div>
                        @php $maxItemTotal = $topItems->max('total') ?: 1; @endphp
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('reports::reports.item_name') }}</th>
                                        <th class="text-end">{{ __('reports::reports.qty_sold') }}</th>
                                        <th class="text-end">{{ __('reports::reports.total') }}</th>
                                        <th class="text-end">{{ __('reports::reports.item_profit') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topItems as $i => $item)
                                        @php
                                            $pct = ($item->total / $maxItemTotal) * 100;
                                            $rankColors = ['bg-warning text-dark','bg-secondary text-white','bg-danger text-white'];
                                            $rankClass  = $rankColors[$i] ?? 'bg-light text-dark';
                                        @endphp
                                        <tr>
                                            <td><span class="table-rank {{ $rankClass }}">{{ $i + 1 }}</span></td>
                                            <td>
                                                <div class="fw-semibold small">{{ $item->name }}</div>
                                                <div class="top-item-bar mt-1" style="width:{{ $pct }}%"></div>
                                            </td>
                                            <td class="text-end small">{{ number_format($item->qty, 2) }}</td>
                                            <td class="text-end fw-bold text-primary small">{{ number_format($item->total, 2) }}</td>
                                            <td class="text-end small {{ $item->profit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($item->profit, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-3">{{ __('reports::reports.no_data') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="section-title">{{ __('reports::reports.top_10_customers') }}</div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('reports::reports.customer_name') }}</th>
                                        <th class="text-end">{{ __('reports::reports.invoices_count') }}</th>
                                        <th class="text-end">{{ __('reports::reports.customer_purchases') }}</th>
                                        <th class="text-end">{{ __('reports::reports.total_discount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topCustomers as $i => $cust)
                                        @php $rc = ['bg-warning text-dark','bg-secondary text-white','bg-danger text-white']; @endphp
                                        <tr>
                                            <td><span class="table-rank {{ $rc[$i] ?? 'bg-light text-dark' }}">{{ $i + 1 }}</span></td>
                                            <td class="fw-semibold small">{{ $cust->acc1Head?->aname ?? '---' }}</td>
                                            <td class="text-end small"><span class="badge bg-info">{{ $cust->invoices_count }}</span></td>
                                            <td class="text-end fw-bold text-primary small">{{ number_format($cust->net_total, 2) }}</td>
                                            <td class="text-end text-danger small">{{ number_format($cust->total_discount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-3">{{ __('reports::reports.no_customer_data') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- المندوبين + ساعات الذروة + أيام الأسبوع --}}
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="section-title">{{ __('reports::reports.sales_by_representative') }}</div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('reports::reports.representative') }}</th>
                                        <th class="text-end">{{ __('reports::reports.rep_invoices') }}</th>
                                        <th class="text-end">{{ __('reports::reports.net_sales') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($salesByRep as $rep)
                                        <tr>
                                            <td class="small">{{ $rep->employee?->aname ?? '---' }}</td>
                                            <td class="text-end small"><span class="badge bg-secondary">{{ $rep->invoices_count }}</span></td>
                                            <td class="text-end fw-bold text-primary small">{{ number_format($rep->net_total, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-muted py-3">{{ __('reports::reports.no_rep_data') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="section-title">{{ __('reports::reports.peak_hours_analysis') }}</div>
                        <div class="chart-container"><canvas id="hourlyChart"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="section-title">{{ __('reports::reports.weekday_analysis') }}</div>
                        <div class="chart-container"><canvas id="weekdayChart"></canvas></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- تحليل الخصومات + جدول يومي تفصيلي --}}
        <div class="row g-4 mb-4">
            <div class="col-md-5">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="section-title">{{ __('reports::reports.discount_analysis') }}</div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('reports::reports.discount_user') }}</th>
                                        <th class="text-end">{{ __('reports::reports.discount_invoices') }}</th>
                                        <th class="text-end">{{ __('reports::reports.total_discount') }}</th>
                                        <th class="text-end">{{ __('reports::reports.avg_discount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($discountByUser as $du)
                                        <tr>
                                            <td class="small">{{ $du->user?->name ?? '---' }}</td>
                                            <td class="text-end small"><span class="badge bg-warning text-dark">{{ $du->invoices_count }}</span></td>
                                            <td class="text-end fw-bold text-danger small">{{ number_format($du->total_discount, 2) }}</td>
                                            <td class="text-end small">{{ number_format($du->avg_pct, 1) }}%</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-3">{{ __('reports::reports.no_discount_data') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="section-title">{{ __('reports::reports.daily_details') }}</div>
                        <div class="table-responsive" style="max-height:320px;overflow-y:auto">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>{{ __('reports::reports.date') }}</th>
                                        <th class="text-end">{{ __('reports::reports.sales_invoice_channel') }}</th>
                                        <th class="text-end">{{ __('reports::reports.cashier_channel') }}</th>
                                        <th class="text-end">{{ __('reports::reports.restaurant_channel') }}</th>
                                        <th class="text-end">{{ __('reports::reports.net_total') }}</th>
                                        <th class="text-end">{{ __('reports::reports.profit') }}</th>
                                        <th class="text-end">{{ __('reports::reports.invoices_count') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($daily as $row)
                                        <tr>
                                            <td class="small">{{ $row->day }}</td>
                                            <td class="text-end small text-primary">{{ number_format($row->sales_total, 2) }}</td>
                                            <td class="text-end small text-success">{{ number_format($row->cashier_total, 2) }}</td>
                                            <td class="text-end small text-warning">{{ number_format($row->restaurant_total, 2) }}</td>
                                            <td class="text-end fw-bold small">{{ number_format($row->net_total, 2) }}</td>
                                            <td class="text-end small {{ $row->daily_profit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($row->daily_profit, 2) }}</td>
                                            <td class="text-end small">{{ $row->invoices_count }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-muted py-3">{{ __('reports::reports.no_data') }}</td></tr>
                                    @endforelse
                                </tbody>
                                @if($daily->count())
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td>{{ __('reports::reports.total') }}</td>
                                        <td class="text-end text-primary">{{ number_format($totals->sales_total ?? 0, 2) }}</td>
                                        <td class="text-end text-success">{{ number_format($totals->cashier_total ?? 0, 2) }}</td>
                                        <td class="text-end text-warning">{{ number_format($totals->restaurant_total ?? 0, 2) }}</td>
                                        <td class="text-end">{{ number_format($netTotal, 2) }}</td>
                                        <td class="text-end {{ $totalProfit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($totalProfit, 2) }}</td>
                                        <td class="text-end">{{ number_format($invoicesCount) }}</td>
                                    </tr>
                                </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function () {
    const dailyLabels  = @json($daily->pluck('day'));
    const dailySales   = @json($daily->pluck('sales_total'));
    const dailyCashier = @json($daily->pluck('cashier_total'));
    const dailyRest    = @json($daily->pluck('restaurant_total'));
    const dailyProfit  = @json($daily->pluck('daily_profit'));

    new Chart(document.getElementById('trendChart'), {
        type: 'bar',
        data: {
            labels: dailyLabels,
            datasets: [
                { label: '{{ __('reports::reports.sales_invoice_channel') }}', data: dailySales,   backgroundColor: '#5e72e4', stack: 'sales' },
                { label: '{{ __('reports::reports.cashier_channel') }}',       data: dailyCashier, backgroundColor: '#2dce89', stack: 'sales' },
                { label: '{{ __('reports::reports.restaurant_channel') }}',    data: dailyRest,    backgroundColor: '#fb6340', stack: 'sales' },
                { label: '{{ __('reports::reports.profit') }}', data: dailyProfit, type: 'line', yAxisID: 'y1', tension: .4, fill: false, borderColor: '#ffd600', backgroundColor: '#ffd600', pointRadius: 3 },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: {
                x: { stacked: true },
                y: { stacked: true, beginAtZero: true },
                y1: { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false } }
            }
        }
    });

    new Chart(document.getElementById('channelChart'), {
        type: 'doughnut',
        data: {
            labels: ['{{ __('reports::reports.sales_invoice_channel') }}', '{{ __('reports::reports.cashier_channel') }}', '{{ __('reports::reports.restaurant_channel') }}'],
            datasets: [{ data: [{{ $totals->sales_total ?? 0 }}, {{ $totals->cashier_total ?? 0 }}, {{ $totals->restaurant_total ?? 0 }}], backgroundColor: ['#5e72e4','#2dce89','#fb6340'] }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    const hourlyLabels = @json($hourly->pluck('hour')->map(fn($h) => $h . ':00'));
    const hourlyOrders = @json($hourly->pluck('orders_count'));
    const hourlySales  = @json($hourly->pluck('net_sales'));

    new Chart(document.getElementById('hourlyChart'), {
        type: 'bar',
        data: {
            labels: hourlyLabels,
            datasets: [
                { label: '{{ __('reports::reports.orders_count') }}', data: hourlyOrders, backgroundColor: '#5e72e4' },
                { label: '{{ __('reports::reports.net_sales') }}', data: hourlySales, type: 'line', yAxisID: 'y1', tension: .4, fill: false, borderColor: '#2dce89', backgroundColor: '#2dce89', pointRadius: 2 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { font: { size: 10 } } } },
            scales: { y: { beginAtZero: true }, y1: { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false } } }
        }
    });

    const wdLabels = @json($weekday->pluck('day_name'));
    const wdOrders = @json($weekday->pluck('orders_count'));
    const wdSales  = @json($weekday->pluck('net_sales'));

    new Chart(document.getElementById('weekdayChart'), {
        type: 'radar',
        data: {
            labels: wdLabels,
            datasets: [
                { label: '{{ __('reports::reports.orders_count') }}', data: wdOrders, backgroundColor: 'rgba(94,114,228,.2)', borderColor: '#5e72e4', pointBackgroundColor: '#5e72e4' },
                { label: '{{ __('reports::reports.net_sales') }}', data: wdSales, backgroundColor: 'rgba(45,206,137,.2)', borderColor: '#2dce89', pointBackgroundColor: '#2dce89' }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top', labels: { font: { size: 10 } } } } }
    });
})();
</script>
@endpush

