@extends('pos::layouts.master')

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-0 fw-bold">
                <i class="fas fa-chart-bar text-primary me-2"></i>
                {{ __('pos.daily_sales_report') }}
            </h4>
            <small class="text-muted">
                <i class="fas fa-calendar-day me-1"></i>
                {{ today()->translatedFormat('l، d F Y') }}
            </small>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                <i class="fas fa-print me-1"></i>{{ __('pos.print') }}
            </button>
            <a href="{{ route('pos.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-arrow-right me-1"></i>{{ __('pos.back_to_pos') }}
            </a>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 text-primary fs-4">
                        <i class="fas fa-cash-register"></i>
                    </div>
                    <div>
                        <div class="text-muted small">{{ __('pos.total_sales') }}</div>
                        <div class="fw-bold fs-5">{{ number_format($total_sales, 2) }}</div>
                        <div class="text-muted small">{{ __('pos.currency') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 text-success fs-4">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div>
                        <div class="text-muted small">{{ __('pos.transactions_count') }}</div>
                        <div class="fw-bold fs-5">{{ $transactions_count }}</div>
                        <div class="text-muted small">{{ __('pos.invoice') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 text-warning fs-4">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div>
                        <div class="text-muted small">{{ __('pos.items_sold') }}</div>
                        <div class="fw-bold fs-5">{{ number_format($items_sold) }}</div>
                        <div class="text-muted small">{{ __('pos.unit') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-info bg-opacity-10 p-3 text-info fs-4">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div>
                        <div class="text-muted small">{{ __('pos.avg_transaction') }}</div>
                        <div class="fw-bold fs-5">{{ number_format($avg_transaction, 2) }}</div>
                        <div class="text-muted small">
                            @if($last_transaction)
                                {{ \Carbon\Carbon::parse($last_transaction)->diffForHumans() }}
                            @else
                                {{ __('pos.no_transactions') }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Left Column --}}
        <div class="col-lg-8">

            {{-- Daily Invoices Table --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-file-invoice text-primary me-2"></i>
                        {{ __('pos.daily_invoices') }}
                    </h6>
                    <span class="badge bg-primary rounded-pill">{{ count($invoices) }}</span>
                </div>
                <div class="card-body p-0">
                    @if(count($invoices) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">#</th>
                                    <th>{{ __('pos.invoice_number') }}</th>
                                    <th>{{ __('pos.customer') }}</th>
                                    <th>{{ __('pos.time') }}</th>
                                    <th>{{ __('pos.discount') }}</th>
                                    <th class="text-end pe-3">{{ __('pos.total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoices as $index => $invoice)
                                <tr>
                                    <td class="ps-3 text-muted small">{{ $index + 1 }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            {{ $invoice['pro_id'] ?? $invoice['id'] }}
                                        </span>
                                    </td>
                                    <td>{{ $invoice['customer_name'] }}</td>
                                    <td class="text-muted small">
                                        {{ \Carbon\Carbon::parse($invoice['created_at'])->format('H:i') }}
                                    </td>
                                    <td>
                                        @if($invoice['fat_disc'] > 0)
                                            <span class="text-danger small">{{ number_format($invoice['fat_disc'], 2) }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3 fw-semibold">{{ number_format($invoice['fat_net'], 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="5" class="ps-3 fw-bold text-end">{{ __('pos.total') }}</td>
                                    <td class="text-end pe-3 fw-bold text-primary">{{ number_format($total_sales, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        {{ __('pos.no_invoices_today') }}
                    </div>
                    @endif
                </div>
            </div>

            {{-- Top Selling Items --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-star text-warning me-2"></i>
                        {{ __('pos.top_selling_items') }}
                    </h6>
                    <span class="badge bg-warning text-dark rounded-pill">{{ __('pos.today') }}</span>
                </div>
                <div class="card-body p-0">
                    @if(count($top_items) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width:50px">{{ __('pos.rank') }}</th>
                                    <th>{{ __('pos.item_name') }}</th>
                                    <th>{{ __('pos.item_code') }}</th>
                                    <th class="text-center">{{ __('pos.qty_sold') }}</th>
                                    <th class="text-center">{{ __('pos.avg_price') }}</th>
                                    <th class="text-end pe-3">{{ __('pos.total_value') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($top_items as $index => $item)
                                <tr>
                                    <td class="ps-3">
                                        @if($index === 0)
                                            <span class="badge bg-warning text-dark">1</span>
                                        @elseif($index === 1)
                                            <span class="badge bg-secondary">2</span>
                                        @elseif($index === 2)
                                            <span class="badge bg-danger">3</span>
                                        @else
                                            <span class="text-muted small">{{ $index + 1 }}</span>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">{{ $item['item_name'] }}</td>
                                    <td class="text-muted small">{{ $item['item_code'] }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">{{ number_format($item['total_qty']) }}</span>
                                    </td>
                                    <td class="text-center text-muted small">{{ number_format($item['avg_price'], 2) }}</td>
                                    <td class="text-end pe-3 fw-semibold text-success">{{ number_format($item['total_value'], 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
                        {{ __('pos.no_items_sold_today') }}
                    </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- Right Column --}}
        <div class="col-lg-4">

            {{-- Sales Summary --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-chart-pie text-success me-2"></i>
                        {{ __('pos.sales_summary') }}
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted small">{{ __('pos.total_sales') }}</span>
                        <span class="fw-bold text-primary">{{ number_format($total_sales, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted small">{{ __('pos.transactions_count') }}</span>
                        <span class="fw-bold">{{ $transactions_count }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted small">{{ __('pos.items_sold') }}</span>
                        <span class="fw-bold">{{ number_format($items_sold) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted small">{{ __('pos.avg_transaction') }}</span>
                        <span class="fw-bold text-info">{{ number_format($avg_transaction, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Payment Analysis --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-credit-card text-info me-2"></i>
                        {{ __('pos.payment_analysis') }}
                    </h6>
                </div>
                <div class="card-body">
                    @php
                        $cashPct    = $total_sales > 0 ? round($payment_analysis['cash_total'] / $total_sales * 100) : 0;
                        $partialPct = $total_sales > 0 ? round($payment_analysis['partial_total'] / $total_sales * 100) : 0;
                        $deferPct   = $total_sales > 0 ? round($payment_analysis['deferred_total'] / $total_sales * 100) : 0;
                    @endphp

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small text-muted">
                                <i class="fas fa-money-bill-wave text-success me-1"></i>
                                {{ __('pos.cash_payment') }}
                            </span>
                            <span class="small fw-semibold">{{ number_format($payment_analysis['cash_total'], 2) }}</span>
                        </div>
                        <div class="progress" style="height:6px">
                            <div class="progress-bar bg-success" style="width:{{ $cashPct }}%"></div>
                        </div>
                        <div class="text-end text-muted" style="font-size:11px">{{ $cashPct }}%</div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small text-muted">
                                <i class="fas fa-hand-holding-usd text-warning me-1"></i>
                                {{ __('pos.partial_payment') }}
                            </span>
                            <span class="small fw-semibold">{{ number_format($payment_analysis['partial_total'], 2) }}</span>
                        </div>
                        <div class="progress" style="height:6px">
                            <div class="progress-bar bg-warning" style="width:{{ $partialPct }}%"></div>
                        </div>
                        <div class="text-end text-muted" style="font-size:11px">{{ $partialPct }}%</div>
                    </div>

                    <div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small text-muted">
                                <i class="fas fa-clock text-danger me-1"></i>
                                {{ __('pos.deferred_payment') }}
                            </span>
                            <span class="small fw-semibold">{{ number_format($payment_analysis['deferred_total'], 2) }}</span>
                        </div>
                        <div class="progress" style="height:6px">
                            <div class="progress-bar bg-danger" style="width:{{ $deferPct }}%"></div>
                        </div>
                        <div class="text-end text-muted" style="font-size:11px">{{ $deferPct }}%</div>
                    </div>
                </div>
            </div>

            {{-- Report Meta --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2 text-muted small">
                        <i class="fas fa-calendar-check text-primary"></i>
                        <span>{{ now()->format('Y-m-d H:i') }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-2 text-muted small">
                        <i class="fas fa-user text-primary"></i>
                        <span>{{ auth()->user()->name }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 text-muted small">
                        <i class="fas fa-store text-primary"></i>
                        <span>{{ config('app.name') }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
