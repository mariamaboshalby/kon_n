@extends('admin.dashboard')

@section('sidebar')
    @include('components.sidebar.reports')
@endsection

@section('content')
    @include('components.breadcrumb', [
        'title' => __('reports::reports.cashier_performance_report'),
        'breadcrumb_items' => [
            ['label' => __('reports::reports.home'), 'url' => route('admin.dashboard')],
            ['label' => __('reports::reports.sales_reports')],
            ['label' => __('reports::reports.cashier_performance_report')],
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
                        <a href="{{ route('reports.pos.cashier-performance') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- بطاقات الإجماليات --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1"><i class="fas fa-layer-group text-primary me-1"></i>{{ __('reports::reports.total_shifts') }}</div>
                        <div class="fs-3 fw-bold text-primary">{{ number_format($shiftTotals->total_shifts ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1"><i class="fas fa-cash-register text-success me-1"></i>{{ __('reports::reports.total_opening_balance') }}</div>
                        <div class="fs-3 fw-bold text-success">{{ number_format($shiftTotals->total_opening ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1"><i class="fas fa-cash-register text-warning me-1"></i>{{ __('reports::reports.total_closing_balance') }}</div>
                        <div class="fs-3 fw-bold text-warning">{{ number_format($shiftTotals->total_closing ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        @php $diff = $shiftTotals->total_cash_diff ?? 0; @endphp
                        <div class="text-muted small mb-1"><i class="fas fa-balance-scale me-1 {{ $diff >= 0 ? 'text-success' : 'text-danger' }}"></i>{{ __('reports::reports.cash_difference') }}</div>
                        <div class="fs-3 fw-bold {{ $diff >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($diff, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            {{-- أداء الكاشيرين --}}
            <div class="col-md-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-users text-primary me-2"></i>{{ __('reports::reports.cashier_sales_summary') }}
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('reports::reports.cashier') }}</th>
                                        <th class="text-end">{{ __('reports::reports.invoices_count') }}</th>
                                        <th class="text-end">{{ __('reports::reports.net_sales') }}</th>
                                        <th class="text-end">{{ __('reports::reports.total_discount') }}</th>
                                        <th class="text-end">{{ __('reports::reports.average_invoice') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($cashierSales as $cs)
                                        <tr>
                                            <td><i class="fas fa-user-circle text-muted me-1"></i>{{ $cs->user?->name ?? __('reports::reports.unspecified') }}</td>
                                            <td class="text-end"><span class="badge bg-info">{{ number_format($cs->invoices_count) }}</span></td>
                                            <td class="text-end fw-bold text-success">{{ number_format($cs->net_sales, 2) }}</td>
                                            <td class="text-end text-warning">{{ number_format($cs->total_discount, 2) }}</td>
                                            <td class="text-end">{{ number_format($cs->avg_invoice, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-3">{{ __('reports::reports.no_data_available') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- رسم بياني للمبيعات --}}
            <div class="col-md-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-chart-pie text-warning me-2"></i>{{ __('reports::reports.sales_by_cashier') }}
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <canvas id="cashierChart" height="220"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- جدول الورديات --}}
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold">
                <i class="fas fa-clock text-secondary me-2"></i>{{ __('reports::reports.shifts_details') }}
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('reports::reports.cashier') }}</th>
                                <th>{{ __('reports::reports.branch') }}</th>
                                <th>{{ __('reports::reports.shift_opened') }}</th>
                                <th>{{ __('reports::reports.shift_closed') }}</th>
                                <th class="text-end">{{ __('reports::reports.opening_balance') }}</th>
                                <th class="text-end">{{ __('reports::reports.closing_balance') }}</th>
                                <th class="text-end">{{ __('reports::reports.cash_difference') }}</th>
                                <th class="text-center">{{ __('reports::reports.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($shifts as $shift)
                                @php $cashDiff = ($shift->closing_balance ?? 0) - ($shift->opening_balance ?? 0); @endphp
                                <tr>
                                    <td>{{ $shift->user?->name ?? '---' }}</td>
                                    <td>{{ $shift->branch?->name ?? '---' }}</td>
                                    <td>{{ $shift->opened_at }}</td>
                                    <td>{{ $shift->closed_at ?? '---' }}</td>
                                    <td class="text-end">{{ number_format($shift->opening_balance ?? 0, 2) }}</td>
                                    <td class="text-end">{{ number_format($shift->closing_balance ?? 0, 2) }}</td>
                                    <td class="text-end fw-bold {{ $cashDiff >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($cashDiff, 2) }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $shift->status === 'open' ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $shift->status === 'open' ? __('reports::reports.open') : __('reports::reports.closed') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">{{ __('reports::reports.no_data_available') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($shifts->hasPages())
                <div class="card-footer">{{ $shifts->appends(request()->query())->links() }}</div>
            @endif
        </div>

    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function () {
    const labels = @json($cashierSales->map(fn($c) => $c->user?->name ?? 'غير محدد'));
    const data   = @json($cashierSales->pluck('net_sales'));
    if (labels.length) {
        new Chart(document.getElementById('cashierChart'), {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{ data, backgroundColor: ['#198754','#0d6efd','#ffc107','#dc3545','#0dcaf0','#6f42c1'] }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }
})();
</script>
@endpush
