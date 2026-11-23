@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.reports')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('reports::reports.peak_hours_report'),
        'breadcrumb_items' => [
            ['label' => __('reports::reports.home'), 'url' => route('admin.dashboard')],
            ['label' => __('reports::reports.sales_reports')],
            ['label' => __('reports::reports.peak_hours_report')],
        ],
    ])

    <div class="container-fluid">

        {{-- فلاتر --}}
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
                        <label class="form-label fw-bold">{{ __('reports::reports.sale_type') }}</label>
                        <select name="sale_type" class="form-select">
                            <option value="">{{ __('reports::reports.cashier_and_restaurant') }}</option>
                            <option value="102" {{ request('sale_type') == '102' ? 'selected' : '' }}>{{ __('pos.cashier_pos') }}</option>
                            <option value="103" {{ request('sale_type') == '103' ? 'selected' : '' }}>{{ __('pos.restaurant_pos') }}</option>
                        </select>
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
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fas fa-search me-1"></i> {{ __('reports::reports.search') }}
                        </button>
                        <a href="{{ route('reports.pos.peak-hours') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- بطاقات الذروة --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">{{ __('reports::reports.total_orders') }}</div>
                        <div class="fs-3 fw-bold text-primary">{{ number_format($totals->total_orders ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">{{ __('reports::reports.net_total') }}</div>
                        <div class="fs-3 fw-bold text-success">{{ number_format($totals->net_sales ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1"><i class="fas fa-fire text-danger me-1"></i>{{ __('reports::reports.peak_hour') }}</div>
                        <div class="fs-3 fw-bold text-danger">
                            {{ $peakHour ? str_pad($peakHour->hour, 2, '0', STR_PAD_LEFT) . ':00' : '---' }}
                        </div>
                        <div class="text-muted small">{{ number_format($peakHour?->orders_count ?? 0) }} {{ __('reports::reports.orders') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1"><i class="fas fa-calendar-day text-warning me-1"></i>{{ __('reports::reports.peak_day') }}</div>
                        <div class="fs-3 fw-bold text-warning">{{ $peakWeekday?->day_name ?? '---' }}</div>
                        <div class="text-muted small">{{ number_format($peakWeekday?->orders_count ?? 0) }} {{ __('reports::reports.orders') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            {{-- رسم بياني ساعي --}}
            <div class="col-md-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-chart-bar text-primary me-2"></i>{{ __('reports::reports.hourly_distribution') }}
                    </div>
                    <div class="card-body">
                        <canvas id="hourlyChart" height="130"></canvas>
                    </div>
                </div>
            </div>

            {{-- رسم بياني أيام الأسبوع --}}
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-calendar-week text-warning me-2"></i>{{ __('reports::reports.weekday_distribution') }}
                    </div>
                    <div class="card-body">
                        <canvas id="weekdayChart" height="220"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- جدول تفصيلي ساعي --}}
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold">
                <i class="fas fa-table text-secondary me-2"></i>{{ __('reports::reports.hourly_details') }}
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('reports::reports.hour') }}</th>
                                <th class="text-end">{{ __('reports::reports.total_orders') }}</th>
                                <th class="text-end">{{ __('pos.cashier_pos') }}</th>
                                <th class="text-end">{{ __('pos.restaurant_pos') }}</th>
                                <th class="text-end">{{ __('reports::reports.net_total') }}</th>
                                <th class="text-end">{{ __('reports::reports.average_invoice') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $maxOrders = $hourly->max('orders_count') ?: 1; @endphp
                            @forelse($hourly as $row)
                                @php $pct = round($row->orders_count / $maxOrders * 100); @endphp
                                <tr>
                                    <td><strong>{{ str_pad($row->hour, 2, '0', STR_PAD_LEFT) }}:00</strong></td>
                                    <td class="text-end">
                                        <div class="d-flex align-items-center justify-content-end gap-2">
                                            <div class="progress flex-fill" style="height:8px;max-width:80px">
                                                <div class="progress-bar bg-primary" style="width:{{ $pct }}%"></div>
                                            </div>
                                            <span class="badge bg-primary">{{ number_format($row->orders_count) }}</span>
                                        </div>
                                    </td>
                                    <td class="text-end text-success">{{ number_format($row->cashier_count) }}</td>
                                    <td class="text-end text-warning">{{ number_format($row->restaurant_count) }}</td>
                                    <td class="text-end fw-bold">{{ number_format($row->net_sales, 2) }}</td>
                                    <td class="text-end">{{ number_format($row->avg_order, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">{{ __('reports::reports.no_data_available') }}</td></tr>
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
        $hourlyMap     = $hourly->keyBy('hour');
        $hours24       = collect(range(0, 23));
        $hourlyLabels  = $hours24->map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00');
        $hourlyCashier = $hours24->map(fn($h) => (int)   ($hourlyMap->get($h)?->cashier_count    ?? 0));
        $hourlyRest    = $hours24->map(fn($h) => (int)   ($hourlyMap->get($h)?->restaurant_count ?? 0));
        $hourlySales   = $hours24->map(fn($h) => (float) ($hourlyMap->get($h)?->net_sales        ?? 0));
    @endphp
    const hours   = @json($hourlyLabels);
    const cashier = @json($hourlyCashier);
    const rest    = @json($hourlyRest);
    const sales   = @json($hourlySales);

    new Chart(document.getElementById('hourlyChart'), {
        type: 'bar',
        data: {
            labels: hours,
            datasets: [
                { label: '{{ __('pos.cashier_pos') }}',    data: cashier, backgroundColor: '#198754', stack: 'orders' },
                { label: '{{ __('pos.restaurant_pos') }}', data: rest,    backgroundColor: '#ffc107', stack: 'orders' },
                { label: '{{ __('reports::reports.net_total') }}', data: sales, type: 'line', borderColor: '#dc3545', backgroundColor: 'transparent', yAxisID: 'y2', tension: 0.4 }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: {
                x: { stacked: true },
                y:  { stacked: true, beginAtZero: true, position: 'left' },
                y2: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } }
            }
        }
    });

    const days    = @json($weekday->pluck('day_name'));
    const wOrders = @json($weekday->pluck('orders_count'));
    new Chart(document.getElementById('weekdayChart'), {
        type: 'polarArea',
        data: {
            labels: days,
            datasets: [{ data: wOrders, backgroundColor: ['#0d6efd','#198754','#ffc107','#dc3545','#0dcaf0','#6f42c1','#fd7e14'] }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
})();
</script>
@endpush
