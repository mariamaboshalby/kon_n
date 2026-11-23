@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.reports')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('reports::reports.discounts_returns_report'),
        'breadcrumb_items' => [
            ['label' => __('reports::reports.home'), 'url' => route('admin.dashboard')],
            ['label' => __('reports::reports.sales_reports')],
            ['label' => __('reports::reports.discounts_returns_report')],
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
                        <label class="form-label fw-bold">{{ __('reports::reports.cashier') }}</label>
                        <select name="user_id" class="form-select">
                            <option value="">{{ __('reports::reports.all') }}</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
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
                        <a href="{{ route('reports.pos.discounts-returns') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- بطاقات الخصومات --}}
        <h6 class="text-muted fw-bold mb-3"><i class="fas fa-tags me-2"></i>{{ __('reports::reports.discounts_summary') }}</h6>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">{{ __('reports::reports.total_discount') }}</div>
                        <div class="fs-3 fw-bold text-danger">{{ number_format($discountTotals->total_discount ?? 0, 2) }}</div>
                        <div class="text-muted small">{{ __('reports::reports.avg_discount_pct') }}: {{ number_format($discountTotals->avg_discount_pct ?? 0, 1) }}%</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">{{ __('reports::reports.discounted_invoices') }}</div>
                        <div class="fs-3 fw-bold text-warning">{{ number_format($discountTotals->discounted_invoices ?? 0) }}</div>
                        <div class="text-muted small">{{ __('reports::reports.of') }} {{ number_format($discountTotals->invoices_count ?? 0) }} {{ __('reports::reports.invoice') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1"><i class="fas fa-cash-register text-success me-1"></i>{{ __('pos.cashier_pos') }}</div>
                        <div class="fs-3 fw-bold text-success">{{ number_format($discountTotals->cashier_discount ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1"><i class="fas fa-utensils text-warning me-1"></i>{{ __('pos.restaurant_pos') }}</div>
                        <div class="fs-3 fw-bold text-warning">{{ number_format($discountTotals->restaurant_discount ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1"><i class="fas fa-file-invoice text-primary me-1"></i>{{ __('reports::reports.sales_invoice') }}</div>
                        <div class="fs-3 fw-bold text-primary">{{ number_format($discountTotals->sales_discount ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- بطاقات المرتجعات --}}
        <h6 class="text-muted fw-bold mb-3"><i class="fas fa-undo me-2"></i>{{ __('reports::reports.returns_summary') }}</h6>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">{{ __('reports::reports.total_returns') }}</div>
                        <div class="fs-3 fw-bold text-danger">{{ number_format($returnTotals->returns_count ?? 0) }}</div>
                        <div class="text-muted small">{{ __('reports::reports.value') }}: {{ number_format($returnTotals->returns_value ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">{{ __('reports::reports.sales_return') }}</div>
                        <div class="fs-3 fw-bold text-warning">{{ number_format($returnTotals->sales_returns ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">{{ __('reports::reports.cashier_return') }}</div>
                        <div class="fs-3 fw-bold text-info">{{ number_format($returnTotals->cashier_returns ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        @php $returnRate = ($discountTotals->net_sales ?? 0) > 0 ? (($returnTotals->returns_value ?? 0) / $discountTotals->net_sales * 100) : 0; @endphp
                        <div class="text-muted small mb-1">{{ __('reports::reports.return_rate') }}</div>
                        <div class="fs-3 fw-bold {{ $returnRate > 5 ? 'text-danger' : 'text-success' }}">{{ number_format($returnRate, 2) }}%</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            {{-- خصومات حسب الكاشير --}}
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-user-tag text-danger me-2"></i>{{ __('reports::reports.discounts_by_cashier') }}
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('reports::reports.cashier') }}</th>
                                        <th class="text-end">{{ __('reports::reports.invoices_count') }}</th>
                                        <th class="text-end">{{ __('reports::reports.total_discount') }}</th>
                                        <th class="text-end">{{ __('reports::reports.avg_discount_pct') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($discountByUser as $du)
                                        <tr>
                                            <td>{{ $du->user?->name ?? __('reports::reports.unspecified') }}</td>
                                            <td class="text-end"><span class="badge bg-info">{{ number_format($du->invoices_count) }}</span></td>
                                            <td class="text-end fw-bold text-danger">{{ number_format($du->total_discount, 2) }}</td>
                                            <td class="text-end">
                                                <span class="badge {{ ($du->avg_pct ?? 0) > 10 ? 'bg-danger' : 'bg-warning text-dark' }}">
                                                    {{ number_format($du->avg_pct ?? 0, 1) }}%
                                                </span>
                                            </td>
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

            {{-- رسم بياني يومي --}}
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-chart-bar text-primary me-2"></i>{{ __('reports::reports.daily_discounts') }}
                    </div>
                    <div class="card-body">
                        <canvas id="discountChart" height="200"></canvas>
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
    const days      = @json($dailyDiscounts->pluck('day'));
    const discounts = @json($dailyDiscounts->pluck('discount'));
    const sales     = @json($dailyDiscounts->pluck('net_sales'));

    new Chart(document.getElementById('discountChart'), {
        type: 'bar',
        data: {
            labels: days,
            datasets: [
                { label: '{{ __('reports::reports.net_sales') }}',     data: sales,     backgroundColor: 'rgba(25,135,84,.6)',  yAxisID: 'y1' },
                { label: '{{ __('reports::reports.total_discount') }}', data: discounts, backgroundColor: 'rgba(220,53,69,.8)',  yAxisID: 'y2', type: 'line', tension: 0.4 }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: {
                y1: { type: 'linear', position: 'left',  beginAtZero: true },
                y2: { type: 'linear', position: 'right', beginAtZero: true, grid: { drawOnChartArea: false } }
            }
        }
    });
})();
</script>
@endpush
